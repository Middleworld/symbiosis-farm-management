<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\EmbeddingService;
use Smalot\PdfParser\Parser;

class ProcessRagFile implements ShouldQueue
{
    use Queueable;

    protected $filePath;
    protected $title;
    protected $rowsPerChunk;
    protected $progressFile;
    protected $fileId;

    /**
     * Create a new job instance.
     */
    public function __construct(string $filePath, string $title, int $rowsPerChunk = 10, ?string $progressFile = null)
    {
        $this->filePath = $filePath;
        $this->title = $title;
        $this->rowsPerChunk = $rowsPerChunk;
        $this->progressFile = $progressFile;
        $this->fileId = md5($filePath . time()); // Unique ID for this file processing instance
    }

    /**
     * Get file information for display purposes
     */
    public function getFileInfo(): array
    {
        return [
            'filePath' => $this->filePath,
            'filename' => basename($this->filePath),
            'title' => $this->title,
            'extension' => strtolower(pathinfo($this->filePath, PATHINFO_EXTENSION)),
            'rowsPerChunk' => $this->rowsPerChunk,
            'fileId' => $this->fileId,
        ];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info("Starting RAG file processing job for: {$this->filePath}");

            if (!file_exists($this->filePath)) {
                $this->logError("File does not exist: {$this->filePath}");
                return;
            }

            $this->updateProgress(['current_file' => basename($this->filePath), 'status' => 'processing']);

            // Initialize file-specific progress
            $this->updateFileProgress($this->fileId, [
                'filename' => basename($this->filePath),
                'status' => 'processing',
                'started_at' => now()->toDateTimeString(),
                'total_chunks' => 0,
                'processed_chunks' => 0,
                'current_chunk' => 0
            ]);

            $extension = strtolower(pathinfo($this->filePath, PATHINFO_EXTENSION));

            if ($extension === 'pdf') {
                $chunks = $this->processPdfFile($this->filePath);
            } elseif (in_array($extension, ['csv', 'txt'])) {
                $data = $this->readCsv($this->filePath);
                if (empty($data)) {
                    $this->logError("Skipping empty or invalid file: {$this->filePath}");
                    return;
                }
                $chunks = $this->createTextChunks($data, $this->rowsPerChunk);
            } else {
                $this->logError("Unsupported file type: {$extension}");
                return;
            }

            Log::info("Created " . count($chunks) . " text chunks for {$this->filePath}");

            $this->updateProgress(['total_chunks' => count($chunks)]);

            // Update file-specific progress with total chunks
            $this->updateFileProgress($this->fileId, [
                'total_chunks' => count($chunks)
            ]);

            $this->processChunks($chunks, $this->title, basename($this->filePath));

            // Mark this file as completed
            $this->updateProgress([
                'processed_files' => ($this->progress['processed_files'] ?? 0) + 1,
                'status' => 'completed'
            ]);

            Log::info("Completed RAG file processing job for: {$this->filePath}");

        } catch (\Exception $e) {
            $this->logError("Job failed for {$this->filePath}: " . $e->getMessage());
            throw $e;
        }
    }

    private function readCsv(string $filePath): array
    {
        $data = [];
        if (($handle = fopen($filePath, 'r')) !== false) {
            $header = fgetcsv($handle);
            while (($row = fgetcsv($handle)) !== false) {
                if (count($header) === count($row)) {
                    $data[] = array_combine($header, $row);
                }
            }
            fclose($handle);
        }
        return $data;
    }

    private function createTextChunks(array $data, int $rowsPerChunk): array
    {
        $chunks = [];
        $currentChunk = [];

        foreach ($data as $row) {
            $currentChunk[] = $this->rowToText($row);

            if (count($currentChunk) >= $rowsPerChunk) {
                $chunks[] = implode("\n\n", $currentChunk);
                $currentChunk = [];
            }
        }

        if (!empty($currentChunk)) {
            $chunks[] = implode("\n\n", $currentChunk);
        }

        return $chunks;
    }

    private function rowToText(array $row): string
    {
        $parts = [];
        foreach ($row as $key => $value) {
            if (!empty($value)) {
                $parts[] = "{$key}: {$value}";
            }
        }
        return implode(', ', $parts);
    }

    private function processChunks(array $chunks, string $title, string $source)
    {
        $embeddingService = app(EmbeddingService::class);
        $ragConnection = DB::connection('pgsql_rag');
        $processed = 0;

        foreach ($chunks as $index => $chunk) {
            Log::info("Processing chunk " . ($index + 1) . "/" . count($chunks) . " for {$source}");

            // Generate embedding
            $vector = $embeddingService->embed($chunk);

            if (!$vector) {
                $this->logError("Failed to generate embedding for chunk {$index} in {$source}");
                continue;
            }

            // Store in database
            $vectorStr = '[' . implode(',', $vector) . ']';

            try {
                $ragConnection->table('general_knowledge')->insert([
                    'title' => $title,
                    'content' => $chunk,
                    'source' => $source,
                    'chunk_index' => $index,
                    'searchable_content' => $chunk,
                    'embedding' => $vectorStr,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $processed++;

                // Update progress every 10 chunks
                if ($this->progressFile && $processed % 10 == 0) {
                    $this->updateProgress([
                        'processed_chunks' => ($this->progress['processed_chunks'] ?? 0) + 10,
                        'current_chunk' => $index + 1
                    ]);
                    // Update file-specific progress
                    $this->updateFileProgress($this->fileId, [
                        'processed_chunks' => $processed,
                        'current_chunk' => $index + 1
                    ]);
                }
            } catch (\Exception $e) {
                $this->logError("Database error for chunk {$index} in {$source}: " . $e->getMessage());
                continue;
            }
        }

        // Final progress update
        if ($this->progressFile) {
            $this->updateProgress([
                'processed_chunks' => ($this->progress['processed_chunks'] ?? 0) + ($processed % 10)
            ]);
            // Final file-specific progress update
            $this->updateFileProgress($this->fileId, [
                'processed_chunks' => $processed,
                'current_chunk' => count($chunks),
                'status' => 'completed',
                'completed_at' => now()->toDateTimeString()
            ]);
        }

        Log::info("Processed {$processed} chunks for {$source}");
    }

    private function processPdfFile(string $filePath): array
    {
        try {
            $parser = new Parser();
            $pdf = $parser->parseFile($filePath);
            $text = $pdf->getText();

            if (empty(trim($text))) {
                $this->logError("No text content found in PDF: {$filePath}");
                return [];
            }

            // Split text into chunks based on paragraphs or sentences
            $chunks = $this->createTextChunksFromText($text);
            return $chunks;
        } catch (\Exception $e) {
            $this->logError("Error processing PDF {$filePath}: " . $e->getMessage());
            return [];
        }
    }

    private function createTextChunksFromText(string $text, int $chunkSize = 1000): array
    {
        $chunks = [];
        $sentences = preg_split('/(?<=[.!?])\s+/', $text);
        $currentChunk = '';

        foreach ($sentences as $sentence) {
            if (strlen($currentChunk . ' ' . $sentence) > $chunkSize) {
                if (!empty($currentChunk)) {
                    $chunks[] = trim($currentChunk);
                    $currentChunk = $sentence;
                } else {
                    // If a single sentence is too long, split it
                    $chunks[] = trim($sentence);
                }
            } else {
                $currentChunk .= (empty($currentChunk) ? '' : ' ') . $sentence;
            }
        }

        if (!empty($currentChunk)) {
            $chunks[] = trim($currentChunk);
        }

        return $chunks;
    }

    private function updateProgress(array $updates = [])
    {
        if (!$this->progressFile) return;

        if (!isset($this->progress)) {
            $this->progress = [];
        }

        $this->progress = array_merge($this->progress, $updates);
        $this->progress['updated_at'] = now()->toDateTimeString();

        file_put_contents($this->progressFile, json_encode($this->progress, JSON_PRETTY_PRINT));
    }

    private function updateFileProgress(string $fileId, array $updates = [])
    {
        if (!$this->progressFile) return;

        if (!isset($this->progress['files'])) {
            $this->progress['files'] = [];
        }

        if (!isset($this->progress['files'][$fileId])) {
            $this->progress['files'][$fileId] = [];
        }

        $this->progress['files'][$fileId] = array_merge($this->progress['files'][$fileId], $updates);
        $this->progress['files'][$fileId]['updated_at'] = now()->toDateTimeString();

        // Save the updated progress
        file_put_contents($this->progressFile, json_encode($this->progress, JSON_PRETTY_PRINT));
    }

    private function logError(string $message)
    {
        Log::error($message);

        if ($this->progressFile) {
            if (!isset($this->progress['errors'])) {
                $this->progress['errors'] = [];
            }
            $this->progress['errors'][] = [
                'timestamp' => now()->toDateTimeString(),
                'message' => $message
            ];
            $this->updateProgress();
        }
    }
}
