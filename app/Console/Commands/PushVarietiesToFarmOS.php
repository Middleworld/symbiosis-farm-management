<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FarmOSApi;
use App\Models\PlantVariety;
use Illuminate\Support\Facades\Log;

class PushVarietiesToFarmOS extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'farmos:push-varieties 
                            {--variety= : Specific variety ID or name to push}
                            {--filter= : Filter varieties by name (wildcard search)}
                            {--dry-run : Show what would be pushed without actually pushing}';

    /**
     * The console command description.
     */
    protected $description = 'Push local variety data changes back to FarmOS taxonomy (DEV ONLY)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->warn('⚠️  DEV MODE: Pushing local database to FarmOS');
        $this->warn('   This reverses the normal sync direction!');
        $this->newLine();

        $dryRun = $this->option('dry-run');
        $varietyFilter = $this->option('variety');
        $nameFilter = $this->option('filter');

        if ($dryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made to FarmOS');
            $this->newLine();
        }

        try {
            $farmOSApi = app(FarmOSApi::class);

            // Build query
            $query = PlantVariety::whereNotNull('farmos_id')
                                ->where('is_active', true);

            if ($varietyFilter) {
                if (is_numeric($varietyFilter)) {
                    $query->where('id', $varietyFilter);
                } else {
                    $query->where('name', 'like', "%{$varietyFilter}%");
                }
            }

            if ($nameFilter) {
                $query->where('name', 'like', "%{$nameFilter}%");
            }

            $varieties = $query->get();

            if ($varieties->isEmpty()) {
                $this->error('❌ No varieties found matching criteria');
                return 1;
            }

            $this->info("📊 Found {$varieties->count()} varieties to push");
            $this->newLine();

            $pushed = 0;
            $skipped = 0;
            $errors = 0;

            $progressBar = $this->output->createProgressBar($varieties->count());
            $progressBar->start();

            foreach ($varieties as $variety) {
                try {
                    // Prepare data to push
                    $updateData = [
                        'attributes' => [],
                        'relationships' => []
                    ];

                    // Add spacing data if available
                    if ($variety->in_row_spacing_cm) {
                        // Cap at 200cm (FarmOS limit)
                        $spacing = min((float)$variety->in_row_spacing_cm, 200);
                        $updateData['attributes']['in_row_spacing_cm'] = (string)$spacing;
                    }
                    if ($variety->between_row_spacing_cm) {
                        // Cap at 200cm (FarmOS limit)
                        $spacing = min((float)$variety->between_row_spacing_cm, 200);
                        $updateData['attributes']['between_row_spacing_cm'] = (string)$spacing;
                    }
                    
                    // Planting method (normalize values)
                    if ($variety->planting_method) {
                        $methodMap = [
                            'either' => 'both',
                            'transplant' => 'transplant',
                            'direct' => 'direct',
                            'both' => 'both'
                        ];
                        $method = strtolower($variety->planting_method);
                        $updateData['attributes']['planting_method'] = $methodMap[$method] ?? $variety->planting_method;
                    }
                    
                    // Planting depth
                    if ($variety->planting_depth_inches) {
                        $updateData['attributes']['planting_depth_inches'] = (string)$variety->planting_depth_inches;
                    }

                    // Timing data
                    if ($variety->maturity_days) {
                        $updateData['attributes']['maturity_days'] = (int)$variety->maturity_days;
                    }
                    if ($variety->propagation_days) {
                        $updateData['attributes']['propagation_days'] = (int)$variety->propagation_days;
                    }
                    if ($variety->harvest_days) {
                        $updateData['attributes']['harvest_days'] = (int)$variety->harvest_days;
                    }
                    if ($variety->harvest_window_days) {
                        $updateData['attributes']['harvest_window_days'] = (int)$variety->harvest_window_days;
                    }
                    
                    // Season type
                    if ($variety->season_type) {
                        $updateData['attributes']['season_type'] = $variety->season_type;
                    }
                    
                    // Harvest months
                    if ($variety->harvest_start_month) {
                        $updateData['attributes']['harvest_start_month'] = (int)$variety->harvest_start_month;
                    }
                    if ($variety->harvest_end_month) {
                        $updateData['attributes']['harvest_end_month'] = (int)$variety->harvest_end_month;
                    }
                    
                    // Germination data
                    if ($variety->germination_days_min) {
                        $updateData['attributes']['germination_days_min'] = (int)$variety->germination_days_min;
                    }
                    if ($variety->germination_days_max) {
                        $updateData['attributes']['germination_days_max'] = (int)$variety->germination_days_max;
                    }
                    if ($variety->germination_temp_optimal) {
                        // Convert Fahrenheit to Celsius if needed (FarmOS expects Celsius, max 50)
                        $temp = (float)$variety->germination_temp_optimal;
                        if ($temp > 50) {
                            // Likely in Fahrenheit, convert to Celsius
                            $temp = ($temp - 32) * 5 / 9;
                        }
                        $updateData['attributes']['germination_temp_optimal'] = number_format($temp, 2);
                    }
                    
                    // Tolerance
                    if ($variety->frost_tolerance) {
                        $updateData['attributes']['frost_tolerance'] = $variety->frost_tolerance;
                    }
                    
                    // Harvest method (normalize values to match FarmOS list options)
                    if ($variety->harvest_method) {
                        $methodMap = [
                            'single-harvest' => 'single',
                            'once-over' => 'single',
                            'multiple-passes' => 'succession',
                            'continuous' => 'continuous',
                            'cut-and-come-again' => 'cut-and-come-again',
                            'succession' => 'succession',
                            'once' => 'single',
                            'cut_again' => 'cut-and-come-again'
                        ];
                        $method = strtolower(str_replace(' ', '-', $variety->harvest_method));
                        $updateData['attributes']['harvest_method'] = $methodMap[$method] ?? $variety->harvest_method;
                    }

                    // Skip if no data to push
                    if (empty($updateData['attributes']) && empty($updateData['relationships'])) {
                        $skipped++;
                        $progressBar->advance();
                        continue;
                    }

                    if ($dryRun) {
                        $this->newLine();
                        $this->line("  Would push: {$variety->name}");
                        $this->line("    Data: " . json_encode($updateData['attributes'], JSON_PRETTY_PRINT));
                        $pushed++;
                    } else {
                        // Push to FarmOS
                        $result = $farmOSApi->updatePlantTypeTerm($variety->farmos_id, $updateData);

                        if ($result['success'] ?? false) {
                            $pushed++;
                        } else {
                            $errors++;
                            Log::error("Failed to push variety to FarmOS", [
                                'variety' => $variety->name,
                                'error' => $result['error'] ?? 'Unknown error'
                            ]);
                        }
                    }

                    $progressBar->advance();

                } catch (\Exception $e) {
                    $errors++;
                    Log::error("Exception pushing variety: " . $e->getMessage(), [
                        'variety' => $variety->name
                    ]);
                    $progressBar->advance();
                }
            }

            $progressBar->finish();
            $this->newLine();
            $this->newLine();

            $this->info("✅ Push complete!");
            $this->info("📊 Pushed: {$pushed} | Skipped: {$skipped} | Errors: {$errors}");

            if ($dryRun) {
                $this->warn('🔍 This was a DRY RUN - no changes were made');
                $this->info('💡 Run without --dry-run to actually push changes');
            }

        } catch (\Exception $e) {
            $this->error('❌ Push failed: ' . $e->getMessage());
            Log::error('FarmOS variety push failed: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
