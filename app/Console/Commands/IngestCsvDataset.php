<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Services\EmbeddingService;
use Smalot\PdfParser\Parser;

class IngestCsvDataset extends Command
{
    private $progressDir;
    private $progressFile;
    private $pidFile;
    private $progress = [];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rag:ingest-csv {path : Path to CSV/PDF file or directory containing CSV/PDF files} {--title= : Title for the dataset (ignored if directory)} {--rows-per-chunk=10 : Number of rows per text chunk} {--background : Run in background mode with progress tracking}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ingest CSV and PDF datasets into the RAG knowledge base';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $path = $this->argument('path');
        $title = $this->option('title');
        $rowsPerChunk = (int) $this->option('rows-per-chunk');
        $background = $this->option('background');

        // Initialize progress tracking for background mode
        if ($background) {
            $this->initializeProgressTracking();
        }

        if (is_dir($path)) {
            $this->ingestDirectory($path, $rowsPerChunk);
        } else {
            $this->ingestFile($path, $title ?? basename($path, '.csv'), $rowsPerChunk);
        }

        // Clean up progress tracking
        if ($background) {
            $this->cleanupProgressTracking();
        }

        $this->info('Ingestion completed successfully');
        return 0;
    }

    private function ingestDirectory(string $dirPath, int $rowsPerChunk)
    {
        $files = glob($dirPath . '/*.{csv,txt,pdf}', GLOB_BRACE);
        $this->info("Found " . count($files) . " files in directory");

        if ($this->progressFile) {
            $this->updateProgress([
                'total_files' => count($files),
                'total_chunks' => 0 // Will be updated as we process
            ]);
        }

        foreach ($files as $file) {
            $title = pathinfo($file, PATHINFO_FILENAME);
            $this->ingestFile($file, $title, $rowsPerChunk);

            if ($this->progressFile) {
                $this->progress['processed_files']++;
                $this->updateProgress();
            }
        }
    }

    private function ingestFile(string $filePath, string $title, int $rowsPerChunk)
    {
        if (!file_exists($filePath)) {
            $this->logError("File does not exist: {$filePath}");
            return;
        }

        if ($this->progressFile) {
            $this->updateProgress(['current_file' => basename($filePath)]);
        }

        $this->info("Ingesting file: {$filePath}");
        $this->info("Title: {$title}");

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        if ($extension === 'pdf') {
            $chunks = $this->processPdfFile($filePath);
        } elseif (in_array($extension, ['csv', 'txt'])) {
            $data = $this->readCsv($filePath);
            if (empty($data)) {
                $this->logError("Skipping empty or invalid file: {$filePath}");
                return;
            }
            $this->info('File loaded, ' . count($data) . ' rows');
            $chunks = $this->createTextChunks($data, $rowsPerChunk);
        } else {
            $this->logError("Unsupported file type: {$extension}");
            return;
        }

        $this->info("Created " . count($chunks) . " text chunks");

        if ($this->progressFile) {
            $this->progress['total_chunks'] += count($chunks);
            $this->updateProgress();
        }

        // Process chunks
        $this->processChunks($chunks, $title, basename($filePath));
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
            $this->info("Processing chunk " . ($index + 1) . "/" . count($chunks));

            // Generate embedding
            $vector = $embeddingService->embed($chunk);

            if (!$vector) {
                $this->logError("Failed to generate embedding for chunk {$index}");
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
                    $this->progress['processed_chunks'] = ($this->progress['processed_chunks'] ?? 0) + 10;
                    $this->progress['current_chunk'] = $index + 1;
                    $this->updateProgress();
                }
            } catch (\Exception $e) {
                $this->logError("Database error for chunk {$index}: " . $e->getMessage());
                continue;
            }
        }

        // Final progress update for this file
        if ($this->progressFile) {
            $this->progress['processed_chunks'] += $processed % 10; // Add remaining chunks
            $this->updateProgress();
        }

        $this->info("Processed {$processed} chunks");
    }

    private function initializeProgressTracking()
    {
        $this->progressDir = storage_path('logs/rag-ingestion');
        if (!is_dir($this->progressDir)) {
            mkdir($this->progressDir, 0755, true);
        }

        $this->progressFile = $this->progressDir . '/progress.json';
        $this->pidFile = $this->progressDir . '/process.pid';

        // Write PID file
        file_put_contents($this->pidFile, getmypid());

        // Initialize progress
        $this->progress = [
            'status' => 'running',
            'started_at' => now()->toDateTimeString(),
            'total_files' => 0,
            'processed_files' => 0,
            'total_chunks' => 0,
            'processed_chunks' => 0,
            'current_file' => null,
            'current_chunk' => 0,
            'errors' => [],
            'pid' => getmypid()
        ];

        $this->updateProgress();
    }

    private function updateProgress(array $updates = [])
    {
        if (!$this->progressFile) return;

        $this->progress = array_merge($this->progress, $updates);
        $this->progress['updated_at'] = now()->toDateTimeString();

        file_put_contents($this->progressFile, json_encode($this->progress, JSON_PRETTY_PRINT));
    }

    private function cleanupProgressTracking()
    {
        if ($this->progressFile && file_exists($this->progressFile)) {
            $this->updateProgress(['status' => 'completed', 'completed_at' => now()->toDateTimeString()]);
        }

        if ($this->pidFile && file_exists($this->pidFile)) {
            unlink($this->pidFile);
        }
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

    private function logError(string $message)
    {
        if ($this->progressFile) {
            $this->progress['errors'][] = [
                'timestamp' => now()->toDateTimeString(),
                'message' => $message
            ];
            $this->updateProgress();
        }

        $this->error($message);
    }
}
