<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use App\Services\EmbeddingService;

class IngestPdfDocument extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rag:ingest-pdf {file : Path to the PDF file} {--title= : Title for the document} {--chunk-size=1000 : Size of text chunks}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ingest a PDF document into the RAG knowledge base';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = $this->argument('file');
        $title = $this->option('title') ?? basename($filePath, '.pdf');
        $chunkSize = (int) $this->option('chunk-size');

        if (!file_exists($filePath)) {
            $this->error("File does not exist: {$filePath}");
            return 1;
        }

        $this->info("Ingesting PDF: {$filePath}");
        $this->info("Title: {$title}");
        $this->info("Chunk size: {$chunkSize} characters");

        // Extract text from PDF
        $text = $this->extractTextFromPdf($filePath);
        if (!$text) {
            $this->error('Failed to extract text from PDF');
            return 1;
        }

        $this->info('Text extracted, length: ' . strlen($text));

        // Chunk the text
        $chunks = $this->chunkText($text, $chunkSize);
        $this->info("Created " . count($chunks) . " chunks");

        // Process chunks
        $this->processChunks($chunks, $title, basename($filePath));

        $this->info('Ingestion completed successfully');
        return 0;
    }

    private function extractTextFromPdf(string $filePath): ?string
    {
        $result = Process::run(['pdftotext', $filePath, '-']);

        if ($result->successful()) {
            return $result->output();
        }

        $this->error('pdftotext failed: ' . $result->errorOutput());
        return null;
    }

    private function chunkText(string $text, int $chunkSize): array
    {
        $chunks = [];
        $words = explode(' ', $text);
        $currentChunk = '';

        foreach ($words as $word) {
            if (strlen($currentChunk . ' ' . $word) > $chunkSize) {
                if (!empty($currentChunk)) {
                    $chunks[] = trim($currentChunk);
                    $currentChunk = $word;
                } else {
                    $chunks[] = $word;
                    $currentChunk = '';
                }
            } else {
                $currentChunk .= (empty($currentChunk) ? '' : ' ') . $word;
            }
        }

        if (!empty($currentChunk)) {
            $chunks[] = trim($currentChunk);
        }

        return $chunks;
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
                $this->warn("Failed to generate embedding for chunk {$index}");
                continue;
            }

            // Store in database
            $vectorStr = '[' . implode(',', $vector) . ']';

            $ragConnection->table('general_knowledge')->insert([
                'title' => $title,
                'content' => $chunk,
                'source' => $source,
                'chunk_index' => $index,
                'searchable_content' => $chunk, // For now, same as content
                'embedding' => $vectorStr,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $processed++;
        }

        $this->info("Processed {$processed} chunks");
    }
}
