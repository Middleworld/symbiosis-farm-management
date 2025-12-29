<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FarmOSApi;
use App\Models\PlantVariety;
use Illuminate\Support\Facades\Log;

class SyncFarmOSVarieties extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'farmos:sync-varieties:legacy {--force : Force sync all varieties}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync plant varieties from FarmOS to local database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🌾 Starting FarmOS varieties sync...');

        try {
            $farmOSApi = app(FarmOSApi::class);

            // Get plant types to map parent relationships
            $this->info('📋 Fetching plant types from FarmOS...');
            $plantTypes = $farmOSApi->getPlantTypes();
            $plantTypeLookup = [];

            foreach ($plantTypes as $plantType) {
                $plantTypeLookup[$plantType['id']] = $plantType;
            }

            // Get all varieties from FarmOS
            $this->info('📡 Fetching varieties from FarmOS...');
            $farmOSVarieties = $farmOSApi->getVarieties();

            $this->info("📊 Found " . count($farmOSVarieties) . " varieties in FarmOS");

            $synced = 0;
            $skipped = 0;
            $errors = 0;

            foreach ($farmOSVarieties as $farmOSVariety) {
                try {
                    $attributes = $farmOSVariety['attributes'] ?? [];
                    $relationships = $farmOSVariety['relationships'] ?? [];
                    $farmosId = $farmOSVariety['id'];

                    // Check if variety already exists
                    $existingVariety = PlantVariety::where('farmos_id', $farmosId)->first();

                    if ($existingVariety && !$this->option('force')) {
                        $skipped++;
                        continue;
                    }

                    // Get parent plant type (may not exist in flat plant_type structure)
                    $parentId = $relationships['parent']['data'][0]['id'] ?? null;
                    $plantType = null;

                    if ($parentId && isset($plantTypeLookup[$parentId])) {
                        $plantType = $plantTypeLookup[$parentId]['attributes']['name'] ?? null;
                    }

                    // Extract crop family from relationships if available
                    $cropFamily = null;
                    if (isset($relationships['crop_family']['data'][0]['id'])) {
                        $cropFamilyId = $relationships['crop_family']['data'][0]['id'];
                        // You could fetch the actual family name here if needed
                    }

                    // Update or create variety
                    PlantVariety::updateOrCreate(
                        ['farmos_id' => $farmosId],
                        [
                            'name' => $attributes['name'] ?? 'Unknown',
                            'description' => $attributes['description']['value'] ?? '',
                            'scientific_name' => null, // FarmOS doesn't provide this
                            'plant_type' => $plantType,
                            'plant_type_id' => $parentId,
                            'farmos_tid' => $attributes['drupal_internal__tid'] ?? null,
                            
                            // Timing fields
                            'maturity_days' => $attributes['maturity_days'] ?? null,
                            'transplant_days' => $attributes['transplant_days'] ?? null,
                            'harvest_days' => $attributes['harvest_days'] ?? null,
                            
                            // NEW: Spacing fields
                            'in_row_spacing_cm' => $attributes['in_row_spacing_cm'] ?? null,
                            'between_row_spacing_cm' => $attributes['between_row_spacing_cm'] ?? null,
                            'planting_method' => $attributes['planting_method'] ?? null,
                            
                            'farmos_data' => $farmOSVariety,
                            'is_active' => true,
                            'last_synced_at' => now(),
                            'sync_status' => 'synced'
                        ]
                    );

                    $synced++;
                    $this->output->write('.');

                } catch (\Exception $e) {
                    $errors++;
                    Log::error("Failed to sync variety {$farmosId}: " . $e->getMessage());
                    $this->output->write('E');
                }
            }

            $this->newLine();
            $this->info("✅ Sync complete!");
            $this->info("📊 Synced: {$synced} | Skipped: {$skipped} | Errors: {$errors}");

            // Clean up old varieties that no longer exist in FarmOS
            if ($this->option('force')) {
                $this->cleanupOldVarieties($farmOSVarieties);
            }

        } catch (\Exception $e) {
            $this->error('❌ Sync failed: ' . $e->getMessage());
            Log::error('FarmOS varieties sync failed: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * Clean up varieties that no longer exist in FarmOS
     */
    private function cleanupOldVarieties($farmOSVarieties)
    {
        $farmosIds = collect($farmOSVarieties)->pluck('id')->toArray();

        $deleted = PlantVariety::whereNotIn('farmos_id', $farmosIds)
                              ->where('sync_status', 'synced')
                              ->update([
                                  'is_active' => false,
                                  'sync_status' => 'deleted',
                                  'last_synced_at' => now()
                              ]);

        $this->info("🗑️ Marked {$deleted} old varieties as inactive");
    }
}
