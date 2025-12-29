<?php

namespace App\Console\Commands;

use App\Services\EmbeddingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateKnowledgeEmbeddings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rag:generate-embeddings 
                            {--table= : Specific table to process (companion, rotation, calendar)}
                            {--batch-size=10 : Number of entries to process in each batch}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate vector embeddings for all knowledge base entries using Ollama';

    protected EmbeddingService $embeddingService;

    /**
     * Execute the console command.
     */
    public function handle(EmbeddingService $embeddingService): int
    {
        $this->embeddingService = $embeddingService;
        
        // Check if embedding service is available
        if (!$this->embeddingService->isAvailable()) {
            $this->error('Embedding service is not available. Please ensure Ollama is running with nomic-embed-text model.');
            return self::FAILURE;
        }
        
        $this->info('🚀 Starting embedding generation for knowledge base...');
        $this->newLine();
        
        $table = $this->option('table');
        
        if ($table) {
            // Process specific table
            $this->processTable($table);
        } else {
            // Process all tables
            $this->processTable('companion');
            $this->processTable('rotation');
            $this->processTable('calendar');
        }
        
        $this->newLine();
        $this->info('✅ Embedding generation complete!');
        
        return self::SUCCESS;
    }
    
    /**
     * Process a specific table
     */
    protected function processTable(string $tableType): void
    {
        $tableMap = [
            'companion' => 'companion_planting_knowledge',
            'rotation' => 'crop_rotation_knowledge',
            'calendar' => 'uk_planting_calendar',
        ];
        
        if (!isset($tableMap[$tableType])) {
            $this->error("Unknown table type: {$tableType}");
            return;
        }
        
        $tableName = $tableMap[$tableType];
        $pgsql = DB::connection('pgsql_rag');
        
        // Get all entries without embeddings
        $entries = $pgsql->table($tableName)
            ->whereNull('embedding')
            ->get();
        
        if ($entries->isEmpty()) {
            $this->line("✓ {$tableName}: No entries need embeddings");
            return;
        }
        
        $this->info("📊 Processing {$tableName}: {$entries->count()} entries");
        
        $batchSize = (int) $this->option('batch-size');
        $chunks = $entries->chunk($batchSize);
        $totalProcessed = 0;
        
        $progressBar = $this->output->createProgressBar($entries->count());
        $progressBar->start();
        
        foreach ($chunks as $chunk) {
            // Prepare batch of texts to embed
            $batch = [];
            $ids = [];
            
            foreach ($chunk as $entry) {
                $text = $this->embeddingService->formatKnowledgeForEmbedding(
                    (array) $entry,
                    $tableType
                );
                $batch[] = $text;
                $ids[] = $entry->id;
            }
            
            // Generate embeddings for batch
            try {
                $embeddings = $this->embeddingService->embedBatch($batch);
                
                // Update database with embeddings
                foreach ($embeddings as $index => $embedding) {
                    $id = $ids[$index];
                    
                    // Convert array to PostgreSQL vector format: [1.0, 2.0, 3.0]
                    $vectorString = '[' . implode(',', $embedding) . ']';
                    
                    $pgsql->table($tableName)
                        ->where('id', $id)
                        ->update(['embedding' => DB::raw("'{$vectorString}'::vector")]);
                    
                    $totalProcessed++;
                    $progressBar->advance();
                }
                
            } catch (\Exception $e) {
                $this->error("\nError processing batch: " . $e->getMessage());
                $progressBar->advance(count($batch));
            }
        }
        
        $progressBar->finish();
        $this->newLine();
        $this->line("  ✓ Processed {$totalProcessed} entries");
        $this->newLine();
    }
}
