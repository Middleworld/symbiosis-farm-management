<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Models\PlantVariety;
use App\Services\EmbeddingService;

class SyncPlantVarietiesToRAG extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rag:sync-plant-varieties {--force : Force full resync} {--dry-run : Do not write to RAG DB, show preview} {--limit= : Limit number of records to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync accurate PlantVariety data from MySQL to PostgreSQL RAG database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting PlantVariety sync to RAG database...');

        $force = $this->option('force');
        $dryRun = $this->option('dry-run');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;

        if ($force) {
            $this->warn('Force sync enabled - this will truncate existing data');
            if (!$this->confirm('Are you sure you want to truncate the RAG plant_varieties table?')) {
                return;
            }
        }

        try {

            // Get all active plant varieties from MySQL (apply limit if provided)
            $query = PlantVariety::where('is_active', true);
            if ($limit) {
                $query->limit($limit);
            }
            $varieties = $query->get();

            $this->info("Found {$varieties->count()} active plant varieties to sync (limit: " . ($limit ?? 'none') . ")");

            // Switch to PostgreSQL connection for RAG
            $ragConnection = DB::connection('pgsql_rag');

            if ($force) {
                $ragConnection->table('plant_varieties')->truncate();
                $this->info('Truncated existing RAG plant_varieties table');
            }

            $synced = 0;
            $updated = 0;

            foreach ($varieties as $variety) {
                // Create searchable content for RAG
                $searchableContent = $this->createSearchableContent($variety);

                $data = [
                    'farmos_id' => $variety->farmos_id,
                    'name' => $variety->name,
                    'scientific_name' => $variety->scientific_name,
                    'plant_type' => $variety->plant_type,
                    'crop_family' => $variety->crop_family,
                    'season_type' => $variety->season_type,
                    'maturity_days' => $variety->maturity_days,
                    'propagation_days' => $variety->transplant_days,
                    'harvest_window_days' => $variety->harvest_days,
                    'min_temperature' => $variety->min_temperature,
                    'max_temperature' => $variety->max_temperature,
                    'optimal_temperature' => $variety->optimal_temperature,
                    'frost_tolerance' => $variety->frost_tolerance,
                    'companions' => $variety->companions,
                    'in_row_spacing_cm' => $variety->in_row_spacing_cm,
                    'between_row_spacing_cm' => $variety->between_row_spacing_cm,
                    'planting_method' => $variety->planting_method,
                    'searchable_content' => $searchableContent,
                    'last_synced_at' => now(),
                ];

                if ($dryRun) {
                    $this->info("DRY: would upsert mysql_id={$variety->id}, name={$variety->name}");
                    continue;
                }

                // Check if record exists
                $existing = $ragConnection->table('plant_varieties')
                    ->where('farmos_id', $variety->farmos_id)
                    ->first();

                if ($existing) {
                    $ragConnection->table('plant_varieties')
                        ->where('farmos_id', $variety->farmos_id)
                        ->update($data);
                    $updated++;
                } else {
                    $ragConnection->table('plant_varieties')->insert($data);
                    $synced++;
                }
            }

            $this->info("Sync completed: {$synced} new records, {$updated} updated records");

            // Create embeddings for vector search (if you have the service set up)
            if (!$dryRun) {
                $this->createEmbeddings($ragConnection);
            }

        } catch (\Exception $e) {
            $this->error('Sync failed: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * Create searchable content for RAG from variety data
     */
    private function createSearchableContent(PlantVariety $variety): string
    {
        $content = [];

        $content[] = "Plant Variety: {$variety->name}";
        if ($variety->scientific_name) {
            $content[] = "Scientific Name: {$variety->scientific_name}";
        }
        $content[] = "Type: {$variety->plant_type}";
        if ($variety->crop_family) {
            $content[] = "Family: {$variety->crop_family}";
        }
        if ($variety->season_type) {
            $content[] = "Season: {$variety->season_type}";
        }

        // Timing information
        if ($variety->maturity_days) {
            $content[] = "Days to Maturity: {$variety->maturity_days}";
        }
        if ($variety->transplant_days) {
            $content[] = "Days to Transplant: {$variety->transplant_days}";
        }
        if ($variety->harvest_days) {
            $content[] = "Days to Harvest: {$variety->harvest_days}";
        }

        // Temperature requirements
        if ($variety->min_temperature) {
            $content[] = "Minimum Temperature: {$variety->min_temperature}°C";
        }
        if ($variety->max_temperature) {
            $content[] = "Maximum Temperature: {$variety->max_temperature}°C";
        }
        if ($variety->optimal_temperature) {
            $content[] = "Optimal Temperature: {$variety->optimal_temperature}°C";
        }

        // Spacing and planting
        if ($variety->in_row_spacing_cm) {
            $content[] = "In-row Spacing: {$variety->in_row_spacing_cm}cm";
        }
        if ($variety->between_row_spacing_cm) {
            $content[] = "Between-row Spacing: {$variety->between_row_spacing_cm}cm";
        }
        if ($variety->planting_method) {
            $content[] = "Planting Method: {$variety->planting_method}";
        }

        // Other characteristics
        if ($variety->frost_tolerance) {
            $content[] = "Frost Tolerance: {$variety->frost_tolerance}";
        }
        if ($variety->companions) {
            $companions = is_array($variety->companions) ? implode(', ', $variety->companions) : $variety->companions;
            $content[] = "Companion Plants: {$companions}";
        }

        return implode('. ', $content);
    }

    /**
     * Create embeddings for vector search using Ollama
     */
    private function createEmbeddings($ragConnection)
    {
        $this->info('Creating embeddings for vector search...');

        // Get records that need embeddings (where embedding_vector is null)
        $records = $ragConnection->table('plant_varieties')
            ->whereNull('embedding_vector')
            ->whereNotNull('searchable_content')
            ->get(['id', 'searchable_content']);

        if ($records->isEmpty()) {
            $this->info('All records already have embeddings.');
            return;
        }

        $this->info("Processing {$records->count()} records for embeddings...");

        $embeddingService = app(EmbeddingService::class);
        $processed = 0;
        $failed = 0;

        foreach ($records as $record) {
            try {
                $vector = $embeddingService->embed($record->searchable_content);

                if ($vector && is_array($vector) && count($vector) === 768) {
                    // Convert to PostgreSQL vector format
                    $vectorStr = '[' . implode(',', $vector) . ']';

                    $ragConnection->table('plant_varieties')
                        ->where('id', $record->id)
                        ->update(['embedding_vector' => $vectorStr]);

                    $processed++;
                } else {
                    $this->warn("Invalid embedding for record {$record->id}");
                    $failed++;
                }
            } catch (\Exception $e) {
                $this->error("Embedding failed for record {$record->id}: " . $e->getMessage());
                $failed++;
            }
        }

        $this->info("Embeddings created for {$processed} records, {$failed} failed");
    }
}
