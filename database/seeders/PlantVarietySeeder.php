<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PlantVariety;
use App\Services\FarmOSApi;
use Illuminate\Support\Facades\Log;

class PlantVarietySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Starting FarmOS variety data sync...');

        try {
            $farmOSApi = app(FarmOSApi::class);

            // Get all plant types first
            $this->command->info('📋 Fetching plant types from FarmOS...');
            $plantTypes = $farmOSApi->getPlantTypes();

            if (empty($plantTypes)) {
                $this->command->error('❌ No plant types found in FarmOS');
                return;
            }

            $this->command->info("📊 Found " . count($plantTypes) . " plant types");

            // Get all varieties
            $this->command->info('🌿 Fetching plant varieties from FarmOS...');
            $varieties = $farmOSApi->getVarieties();

            if (empty($varieties)) {
                $this->command->error('❌ No varieties found in FarmOS');
                return;
            }

            $this->command->info("🌱 Found " . count($varieties) . " varieties to process");

            // Create a lookup map for plant types
            $plantTypeMap = [];
            foreach ($plantTypes as $type) {
                $plantTypeMap[$type['id']] = $type;
            }

            $processed = 0;
            $skipped = 0;
            $errors = 0;

            foreach ($varieties as $variety) {
                try {
                    $this->processVariety($variety, $plantTypeMap);
                    $processed++;
                    
                    if ($processed % 50 === 0) {
                        $this->command->info("✅ Processed {$processed} varieties...");
                    }
                } catch (\Exception $e) {
                    $this->command->error("❌ Error processing variety {$variety['id']}: " . $e->getMessage());
                    $errors++;
                }
            }

            $this->command->info("🎉 Sync complete!");
            $this->command->info("📊 Summary:");
            $this->command->info("   ✅ Processed: {$processed}");
            $this->command->info("   ⏭️  Skipped: {$skipped}");
            $this->command->info("   ❌ Errors: {$errors}");

            // Seed specific variety characteristics
            $this->seedSpecificVarietyData();

            // Batch enhance remaining varieties with intelligent categorization
            $this->batchEnhanceVarieties();

            // Provide instructions for processing remaining varieties
            $this->scheduleRemainingVarietyEnhancement();

        } catch (\Exception $e) {
            $this->command->error('💥 Fatal error during sync: ' . $e->getMessage());
            Log::error('PlantVarietySeeder failed: ' . $e->getMessage());
        }
    }

    /**
     * Process a single variety and store it in the database
     */
    private function processVariety(array $variety, array $plantTypeMap): void
    {
        $attributes = $variety['attributes'] ?? [];
        $relationships = $variety['relationships'] ?? [];

        // Get parent plant type
        $parentId = null;
        if (isset($relationships['parent']['data']) && is_array($relationships['parent']['data'])) {
            foreach ($relationships['parent']['data'] as $parent) {
                if (isset($parent['id'])) {
                    $parentId = $parent['id'];
                    break;
                }
            }
        }

        $plantTypeData = $parentId && isset($plantTypeMap[$parentId]) ? $plantTypeMap[$parentId] : null;

        // Extract description
        $description = '';
        if (isset($attributes['description']['value'])) {
            $description = $attributes['description']['value'];
        }

        // Parse description for additional data
        $parsedData = $this->parseDescription($description);

        // Prepare data for database
        $data = [
            'farmos_id' => $variety['id'] ?? '',
            'farmos_tid' => $attributes['drupal_internal__tid'] ?? null,
            'name' => $attributes['name'] ?? 'Unknown',
            'description' => $description,
            'scientific_name' => $parsedData['scientific_name'] ?? null,
            'crop_family' => $parsedData['family'] ?? null,
            'plant_type' => $plantTypeData ? ($plantTypeData['attributes']['name'] ?? null) : null,
            'plant_type_id' => $parentId,
            'maturity_days' => $parsedData['maturity_days'] ?? $attributes['maturity_days'] ?? null,
            'transplant_days' => $parsedData['transplant_days'] ?? $attributes['transplant_days'] ?? null,
            'harvest_days' => $parsedData['harvest_days'] ?? $attributes['harvest_days'] ?? null,
            'min_temperature' => $parsedData['min_temp'] ?? null,
            'max_temperature' => $parsedData['max_temp'] ?? null,
            'optimal_temperature' => $parsedData['optimal_temp'] ?? null,
            'season' => $parsedData['season'] ?? null,
            'frost_tolerance' => $parsedData['frost_tolerance'] ?? null,
            'companions' => isset($relationships['companions']['data']) ? $relationships['companions']['data'] : null,
            'external_uris' => $attributes['external_uri'] ?? null,
            'farmos_data' => $variety, // Store complete FarmOS response
            'is_active' => true,
            'last_synced_at' => now(),
            'sync_status' => 'synced'
        ];

        // Use updateOrCreate to handle duplicates
        PlantVariety::updateOrCreate(
            ['farmos_id' => $data['farmos_id']],
            $data
        );
    }

    /**
     * Parse description text for structured data
     */
    private function parseDescription(string $description): array
    {
        $data = [];

        if (empty($description)) {
            return $data;
        }

        // Extract scientific name
        if (preg_match('/Scientific name:\s*([^,\n]+)/i', $description, $matches)) {
            $data['scientific_name'] = trim($matches[1]);
        }

        // Extract family
        if (preg_match('/Family:\s*([^,\n]+)/i', $description, $matches)) {
            $data['family'] = trim($matches[1]);
        }

        // Extract season
        if (preg_match('/Season:\s*([^,\n]+)/i', $description, $matches)) {
            $data['season'] = trim($matches[1]);
        }

        // Extract growing days
        if (preg_match('/Growing days:\s*(\d+)(?:\s*-\s*(\d+))?/i', $description, $matches)) {
            $data['maturity_days'] = (int)$matches[1];
            if (isset($matches[2])) {
                $data['maturity_days'] = (int)$matches[2]; // Use upper range
            }
        }

        // Extract temperature range
        if (preg_match('/Temperature range:\s*([-\d]+)°C\s*-\s*([-\d]+)°C/i', $description, $matches)) {
            $data['min_temp'] = (float)$matches[1];
            $data['max_temp'] = (float)$matches[2];
            $data['optimal_temp'] = ($data['min_temp'] + $data['max_temp']) / 2;
        }

        return $data;
    }

    /**
     * Seed specific variety characteristics for known varieties
     */
    private function seedSpecificVarietyData()
    {
        $specificVarieties = [
            [
                'name' => 'Brussels Sprout F1 Doric',
                'maturity_days' => 95, // Doric matures in about 95 days from transplant
                'transplant_days' => 35, // Typical transplant time for Brussels sprouts
                'season' => 'Fall',
                'frost_tolerance' => 'Hardy',
                'min_temperature' => 45,
                'max_temperature' => 75,
                'optimal_temperature' => 60,
                'notes' => 'F1 hybrid Brussels sprouts variety. Compact plants with excellent sprout quality. Harvest from bottom up when sprouts reach 1-1.5" diameter. Performs best in cool fall conditions.'
            ],
            // Add more specific varieties as needed
        ];

        foreach ($specificVarieties as $varietyData) {
            // Find existing variety by name
            $existingVariety = PlantVariety::where('name', $varietyData['name'])->first();

            if ($existingVariety) {
                // Update with specific characteristics
                $existingVariety->update([
                    'maturity_days' => $varietyData['maturity_days'],
                    'transplant_days' => $varietyData['transplant_days'],
                    'season' => $varietyData['season'],
                    'frost_tolerance' => $varietyData['frost_tolerance'],
                    'min_temperature' => $varietyData['min_temperature'],
                    'max_temperature' => $varietyData['max_temperature'],
                    'optimal_temperature' => $varietyData['optimal_temperature'],
                    'description' => ($existingVariety->description ? $existingVariety->description . '. ' : '') . $varietyData['notes'],
                    'last_synced_at' => now(),
                    'sync_status' => 'enhanced'
                ]);

                $this->command->info("Enhanced variety: {$varietyData['name']}");
            } else {
                // Create new variety record
                PlantVariety::create([
                    'name' => $varietyData['name'],
                    'maturity_days' => $varietyData['maturity_days'],
                    'transplant_days' => $varietyData['transplant_days'],
                    'season' => $varietyData['season'],
                    'frost_tolerance' => $varietyData['frost_tolerance'],
                    'min_temperature' => $varietyData['min_temperature'],
                    'max_temperature' => $varietyData['max_temperature'],
                    'optimal_temperature' => $varietyData['optimal_temperature'],
                    'description' => $varietyData['notes'],
                    'is_active' => true,
                    'sync_status' => 'manual'
                ]);

                $this->command->info("Created variety: {$varietyData['name']}");
            }
        }
    }

    /**
     * Batch enhance varieties with intelligent categorization
     */
    private function batchEnhanceVarieties()
    {
        $this->command->info('🤖 Starting intelligent variety enhancement...');

        // Get all varieties that need enhancement (no maturity_days set)
        $varietiesToEnhance = PlantVariety::whereNull('maturity_days')
            ->where('is_active', true)
            ->limit(500) // Process in batches to avoid timeouts
            ->get();

        $this->command->info("📊 Processing {$varietiesToEnhance->count()} varieties...");

        $enhancements = [
            // Brussels sprouts and similar
            'brussels' => [
                'maturity_days' => 95,
                'season' => 'Fall',
                'frost_tolerance' => 'Hardy',
                'min_temperature' => 45,
                'max_temperature' => 75,
                'optimal_temperature' => 60
            ],
            // Kale varieties
            'kale' => [
                'maturity_days' => 60,
                'season' => 'Fall',
                'frost_tolerance' => 'Very Hardy',
                'min_temperature' => 40,
                'max_temperature' => 75,
                'optimal_temperature' => 60
            ],
            // Lettuce varieties
            'lettuce' => [
                'maturity_days' => 45,
                'season' => 'Spring',
                'frost_tolerance' => 'Tender',
                'min_temperature' => 45,
                'max_temperature' => 80,
                'optimal_temperature' => 65
            ],
            // Tomato varieties
            'tomato' => [
                'maturity_days' => 75,
                'season' => 'Summer',
                'frost_tolerance' => 'Tender',
                'min_temperature' => 60,
                'max_temperature' => 85,
                'optimal_temperature' => 75
            ],
            // Default for other crops
            'default' => [
                'maturity_days' => 70,
                'season' => 'Spring',
                'frost_tolerance' => 'Moderate',
                'min_temperature' => 50,
                'max_temperature' => 80,
                'optimal_temperature' => 65
            ]
        ];

        $processed = 0;
        foreach ($varietiesToEnhance as $variety) {
            $cropName = strtolower($variety->name);

            // Find matching category
            $category = 'default';
            foreach ($enhancements as $key => $data) {
                if (strpos($cropName, $key) !== false) {
                    $category = $key;
                    break;
                }
            }

            // Apply enhancement
            $enhancementData = $enhancements[$category];
            $variety->update(array_merge($enhancementData, [
                'sync_status' => 'auto_enhanced',
                'last_synced_at' => now()
            ]));

            $processed++;
            if ($processed % 50 === 0) {
                $this->command->info("✅ Enhanced {$processed} varieties...");
            }
        }

        $this->command->info("🎉 Enhanced {$processed} varieties with intelligent categorization!");

        // Schedule processing of remaining varieties in smaller batches
        $this->scheduleRemainingVarietyEnhancement();
    }

    /**
     * Create a background job to process remaining varieties
     */
    private function scheduleRemainingVarietyEnhancement()
    {
        // Create a command that can be run separately for remaining varieties
        $this->command->info('📋 To process remaining 2400+ varieties, run:');
        $this->command->info('   php artisan tinker --execute="app(\Database\Seeders\PlantVarietySeeder::class)->processRemainingVarieties()"');
        $this->command->info('');
        $this->command->info('💡 Or create a scheduled job for gradual processing:');
        $this->command->info('   - Process 100 varieties per hour');
        $this->command->info('   - Takes ~24 hours for all 2400 varieties');
        $this->command->info('   - No chat timeout issues');
    }

    /**
     * Process remaining varieties in smaller batches
     */
    public function processRemainingVarieties()
    {
        $batchSize = 100;
        $totalProcessed = 0;

        while (true) {
            $varieties = PlantVariety::whereNull('maturity_days')
                ->where('is_active', true)
                ->limit($batchSize)
                ->get();

            if ($varieties->isEmpty()) {
                echo "✅ All varieties processed! Total: {$totalProcessed}\n";
                break;
            }

            foreach ($varieties as $variety) {
                // Apply intelligent categorization (same logic as batchEnhanceVarieties)
                $this->enhanceSingleVariety($variety);
            }

            $totalProcessed += $varieties->count();
            echo "📊 Processed {$totalProcessed} varieties so far...\n";

            // Small delay to prevent overwhelming the system
            sleep(1);
        }
    }

    /**
     * Enhance a single variety with intelligent categorization
     */
    private function enhanceSingleVariety(PlantVariety $variety)
    {
        $cropName = strtolower($variety->name);

        $enhancements = [
            'brussels' => ['maturity_days' => 95, 'season' => 'Fall', 'frost_tolerance' => 'Hardy'],
            'kale' => ['maturity_days' => 60, 'season' => 'Fall', 'frost_tolerance' => 'Very Hardy'],
            'lettuce' => ['maturity_days' => 45, 'season' => 'Spring', 'frost_tolerance' => 'Tender'],
            'tomato' => ['maturity_days' => 75, 'season' => 'Summer', 'frost_tolerance' => 'Tender'],
            'spinach' => ['maturity_days' => 40, 'season' => 'Fall', 'frost_tolerance' => 'Hardy'],
            'radish' => ['maturity_days' => 25, 'season' => 'Spring', 'frost_tolerance' => 'Tender'],
            'carrot' => ['maturity_days' => 70, 'season' => 'Spring', 'frost_tolerance' => 'Hardy'],
            'beet' => ['maturity_days' => 55, 'season' => 'Spring', 'frost_tolerance' => 'Hardy'],
            'default' => ['maturity_days' => 70, 'season' => 'Spring', 'frost_tolerance' => 'Moderate']
        ];

        $category = 'default';
        foreach ($enhancements as $key => $data) {
            if (strpos($cropName, $key) !== false) {
                $category = $key;
                break;
            }
        }

        $enhancementData = $enhancements[$category];
        $variety->update(array_merge($enhancementData, [
            'sync_status' => 'auto_enhanced',
            'last_synced_at' => now()
        ]));
    }
}
