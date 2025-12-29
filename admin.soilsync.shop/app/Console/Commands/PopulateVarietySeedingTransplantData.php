<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class PopulateVarietySeedingTransplantData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'varieties:populate-seeding-transplant {--limit= : Limit the number of varieties to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populate seeding and transplant data for plant varieties using intelligent fallbacks';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $limit = $this->option('limit');
        $query = \App\Models\PlantVariety::query();
        
        if ($limit) {
            $query->limit($limit);
        }
        
        $varieties = $query->get();
        $total = $varieties->count();
        
        $this->info("Starting seeding/transplant data population for {$total} varieties...");
        
        $progressBar = $this->output->createProgressBar($total);
        $progressBar->start();
        
        $updated = 0;
        
        foreach ($varieties as $variety) {
            try {
                $this->populateSeedingTransplantData($variety);
                $updated++;
                $progressBar->advance();
            } catch (\Exception $e) {
                $this->error("Error processing variety {$variety->name}: {$e->getMessage()}");
                $progressBar->advance();
            }
        }
        
        $progressBar->finish();
        $this->newLine();
        $this->info("✅ Completed: {$updated} varieties updated with seeding/transplant data");
        
        return Command::SUCCESS;
    }

    /**
     * Populate seeding and transplant data for a variety
     */
    private function populateSeedingTransplantData($variety)
    {
        $cropName = strtolower($variety->name);
        $season = $variety->season;
        $maturityDays = $variety->maturity_days ?? 60;
        
        // Initialize data array
        $data = [];
        
        // Determine crop type and apply appropriate seeding/transplant logic
        if ($this->isCoolSeasonCrop($cropName)) {
            $data = $this->getCoolSeasonSeedingData($season, $maturityDays);
        } elseif ($this->isWarmSeasonCrop($cropName)) {
            $data = $this->getWarmSeasonSeedingData($season, $maturityDays);
        } elseif ($this->isLeafyGreen($cropName)) {
            $data = $this->getLeafyGreenSeedingData($season, $maturityDays);
        } elseif ($this->isBrassica($cropName)) {
            $data = $this->getBrassicaSeedingData($season, $maturityDays);
        } elseif ($this->isRootVegetable($cropName)) {
            $data = $this->getRootVegetableSeedingData($season, $maturityDays);
        } elseif ($this->isFruitingCrop($cropName)) {
            $data = $this->getFruitingCropSeedingData($season, $maturityDays);
        } elseif ($this->isHerb($cropName)) {
            $data = $this->getHerbSeedingData($season, $maturityDays);
        } else {
            // Generic fallback
            $data = $this->getGenericSeedingData($season, $maturityDays);
        }
        
        // Apply crop-specific adjustments
        $data = $this->applyCropSpecificAdjustments($cropName, $data);
        
        // Update the variety with the seeding/transplant data
        $variety->update($data);
        
        $this->line("Generating seeding data for {$variety->name} using fallback logic");
    }

    /**
     * Check if crop is cool season
     */
    private function isCoolSeasonCrop($cropName)
    {
        $coolSeasonCrops = ['lettuce', 'spinach', 'kale', 'broccoli', 'cauliflower', 'cabbage', 'brussels sprout', 'carrot', 'beet', 'pea', 'radish'];
        return collect($coolSeasonCrops)->contains(function($crop) use ($cropName) {
            return str_contains($cropName, $crop);
        });
    }

    /**
     * Check if crop is warm season
     */
    private function isWarmSeasonCrop($cropName)
    {
        $warmSeasonCrops = ['tomato', 'pepper', 'eggplant', 'cucumber', 'squash', 'melon', 'bean', 'corn', 'basil'];
        return collect($warmSeasonCrops)->contains(function($crop) use ($cropName) {
            return str_contains($cropName, $crop);
        });
    }

    /**
     * Check if crop is leafy green
     */
    private function isLeafyGreen($cropName)
    {
        $leafyGreens = ['lettuce', 'spinach', 'kale', 'chard', 'arugula', 'mustard', 'collard', 'bok choy'];
        return collect($leafyGreens)->contains(function($crop) use ($cropName) {
            return str_contains($cropName, $crop);
        });
    }

    /**
     * Check if crop is brassica
     */
    private function isBrassica($cropName)
    {
        $brassicas = ['broccoli', 'cauliflower', 'cabbage', 'brussels sprout', 'kale', 'kohlrabi', 'radish', 'turnip'];
        return collect($brassicas)->contains(function($crop) use ($cropName) {
            return str_contains($cropName, $crop);
        });
    }

    /**
     * Check if crop is root vegetable
     */
    private function isRootVegetable($cropName)
    {
        $rootVeggies = ['carrot', 'beet', 'radish', 'turnip', 'parsnip', 'rutabaga'];
        return collect($rootVeggies)->contains(function($crop) use ($cropName) {
            return str_contains($cropName, $crop);
        });
    }

    /**
     * Check if crop is fruiting crop
     */
    private function isFruitingCrop($cropName)
    {
        $fruitingCrops = ['tomato', 'pepper', 'eggplant', 'cucumber', 'squash', 'melon', 'bean'];
        return collect($fruitingCrops)->contains(function($crop) use ($cropName) {
            return str_contains($cropName, $crop);
        });
    }

    /**
     * Check if crop is herb
     */
    private function isHerb($cropName)
    {
        $herbs = ['basil', 'cilantro', 'dill', 'parsley', 'chives', 'oregano', 'thyme', 'rosemary', 'sage'];
        return collect($herbs)->contains(function($crop) use ($cropName) {
            return str_contains($cropName, $crop);
        });
    }

    /**
     * Get cool season seeding data
     */
    private function getCoolSeasonSeedingData($season, $maturityDays)
    {
        $baseData = [
            'germination_days_min' => 3,
            'germination_days_max' => 10,
            'germination_temp_min' => 40.0,
            'germination_temp_max' => 85.0,
            'germination_temp_optimal' => 65.0,
            'planting_depth_inches' => 0.25,
            'requires_light_for_germination' => false,
            'hardening_off_days' => 7,
        ];

        if ($season === 'spring' || !$season) {
            return array_merge($baseData, [
                'indoor_seed_start' => '2025-02-01',
                'indoor_seed_end' => '2025-03-15',
                'outdoor_seed_start' => '2025-03-01',
                'outdoor_seed_end' => '2025-04-15',
                'transplant_start' => '2025-03-15',
                'transplant_end' => '2025-04-30',
                'transplant_window_days' => 14,
                'transplant_soil_temp_min' => 35.0,
                'transplant_soil_temp_max' => 75.0,
                'seed_spacing_inches' => 1.0,
                'row_spacing_inches' => 12.0,
                'seeds_per_hole' => 1,
                'seed_type' => 'raw',
                'seed_starting_notes' => 'Start indoors 4-6 weeks before last frost. Keep soil moist but not waterlogged.',
                'transplant_notes' => 'Transplant after danger of hard frost has passed. Harden off seedlings gradually.',
                'hardening_off_notes' => 'Gradually expose seedlings to outdoor conditions over 7-10 days.'
            ]);
        } elseif ($season === 'fall') {
            return array_merge($baseData, [
                'indoor_seed_start' => '2025-07-01',
                'indoor_seed_end' => '2025-08-15',
                'outdoor_seed_start' => '2025-08-01',
                'outdoor_seed_end' => '2025-09-15',
                'transplant_start' => '2025-08-15',
                'transplant_end' => '2025-09-30',
                'transplant_window_days' => 14,
                'transplant_soil_temp_min' => 35.0,
                'transplant_soil_temp_max' => 75.0,
                'seed_spacing_inches' => 1.0,
                'row_spacing_inches' => 12.0,
                'seeds_per_hole' => 1,
                'seed_type' => 'raw',
                'seed_starting_notes' => 'For fall crops, start seeds indoors 6-8 weeks before first fall frost.',
                'transplant_notes' => 'Transplant in late summer for fall harvest. Ensure adequate spacing for mature plants.',
                'hardening_off_notes' => 'Fall crops often don\'t need as much hardening off as spring crops.'
            ]);
        }

        return $this->getGenericSeedingData($season, $maturityDays);
    }

    /**
     * Get warm season seeding data
     */
    private function getWarmSeasonSeedingData($season, $maturityDays)
    {
        return [
            'indoor_seed_start' => '2025-03-01',
            'indoor_seed_end' => '2025-04-15',
            'outdoor_seed_start' => '2025-05-01',
            'outdoor_seed_end' => '2025-06-15',
            'transplant_start' => '2025-05-15',
            'transplant_end' => '2025-06-30',
            'transplant_window_days' => 10,
            'germination_days_min' => 3,
            'germination_days_max' => 14,
            'germination_temp_min' => 60.0,
            'germination_temp_max' => 95.0,
            'germination_temp_optimal' => 80.0,
            'planting_depth_inches' => 0.5,
            'seed_spacing_inches' => 2.0,
            'row_spacing_inches' => 24.0,
            'seeds_per_hole' => 2,
            'requires_light_for_germination' => false,
            'transplant_soil_temp_min' => 60.0,
            'transplant_soil_temp_max' => 85.0,
            'hardening_off_days' => 7,
            'seed_type' => 'raw',
            'seed_starting_notes' => 'Start indoors 6-8 weeks before last frost. Warm season crops need warm soil to germinate.',
            'transplant_notes' => 'Wait until soil temperature is consistently above 60°F before transplanting.',
            'hardening_off_notes' => 'Warm season crops are more sensitive to cold, so harden off carefully.'
        ];
    }

    /**
     * Get leafy green seeding data
     */
    private function getLeafyGreenSeedingData($season, $maturityDays)
    {
        return [
            'indoor_seed_start' => '2025-02-01',
            'indoor_seed_end' => '2025-08-01',
            'outdoor_seed_start' => '2025-03-01',
            'outdoor_seed_end' => '2025-09-01',
            'transplant_start' => '2025-03-15',
            'transplant_end' => '2025-09-15',
            'transplant_window_days' => 21,
            'germination_days_min' => 2,
            'germination_days_max' => 7,
            'germination_temp_min' => 40.0,
            'germination_temp_max' => 85.0,
            'germination_temp_optimal' => 65.0,
            'planting_depth_inches' => 0.125,
            'seed_spacing_inches' => 0.5,
            'row_spacing_inches' => 8.0,
            'seeds_per_hole' => 3,
            'requires_light_for_germination' => true,
            'transplant_soil_temp_min' => 35.0,
            'transplant_soil_temp_max' => 75.0,
            'hardening_off_days' => 5,
            'seed_type' => 'raw',
            'seed_starting_notes' => 'Leafy greens germinate quickly and often need light to sprout. Keep soil surface moist.',
            'transplant_notes' => 'Leafy greens can be direct seeded or transplanted. Thin seedlings as they grow.',
            'hardening_off_notes' => 'Leafy greens are relatively hardy and don\'t need extensive hardening off.'
        ];
    }

    /**
     * Get brassica seeding data
     */
    private function getBrassicaSeedingData($season, $maturityDays)
    {
        return [
            'indoor_seed_start' => '2025-02-01',
            'indoor_seed_end' => '2025-05-01',
            'outdoor_seed_start' => '2025-03-01',
            'outdoor_seed_end' => '2025-06-01',
            'transplant_start' => '2025-03-15',
            'transplant_end' => '2025-06-15',
            'transplant_window_days' => 14,
            'germination_days_min' => 3,
            'germination_days_max' => 10,
            'germination_temp_min' => 45.0,
            'germination_temp_max' => 85.0,
            'germination_temp_optimal' => 70.0,
            'planting_depth_inches' => 0.25,
            'seed_spacing_inches' => 1.0,
            'row_spacing_inches' => 18.0,
            'seeds_per_hole' => 1,
            'requires_light_for_germination' => false,
            'transplant_soil_temp_min' => 40.0,
            'transplant_soil_temp_max' => 75.0,
            'hardening_off_days' => 7,
            'seed_type' => 'raw',
            'seed_starting_notes' => 'Brassicas prefer cooler temperatures for germination. Avoid overheating seedlings.',
            'transplant_notes' => 'Brassicas grow best when transplanted rather than direct seeded.',
            'hardening_off_notes' => 'Brassicas can handle some cold but protect from hard frosts.'
        ];
    }

    /**
     * Get root vegetable seeding data
     */
    private function getRootVegetableSeedingData($season, $maturityDays)
    {
        return [
            'indoor_seed_start' => '2025-02-01',
            'indoor_seed_end' => '2025-04-01',
            'outdoor_seed_start' => '2025-03-01',
            'outdoor_seed_end' => '2025-05-15',
            'transplant_start' => null, // Root veggies are usually direct seeded
            'transplant_end' => null,
            'transplant_window_days' => null,
            'germination_days_min' => 5,
            'germination_days_max' => 21,
            'germination_temp_min' => 40.0,
            'germination_temp_max' => 85.0,
            'germination_temp_optimal' => 65.0,
            'planting_depth_inches' => 0.25,
            'seed_spacing_inches' => 1.0,
            'row_spacing_inches' => 12.0,
            'seeds_per_hole' => 2,
            'requires_light_for_germination' => false,
            'transplant_soil_temp_min' => null,
            'transplant_soil_temp_max' => null,
            'hardening_off_days' => null,
            'seed_type' => 'raw',
            'seed_starting_notes' => 'Root vegetables are typically direct seeded. Ensure soil is loose and weed-free.',
            'transplant_notes' => 'Most root vegetables do not transplant well. Direct seeding is preferred.',
            'hardening_off_notes' => null
        ];
    }

    /**
     * Get fruiting crop seeding data
     */
    private function getFruitingCropSeedingData($season, $maturityDays)
    {
        return [
            'indoor_seed_start' => '2025-02-15',
            'indoor_seed_end' => '2025-04-01',
            'outdoor_seed_start' => '2025-05-01',
            'outdoor_seed_end' => '2025-06-01',
            'transplant_start' => '2025-05-15',
            'transplant_end' => '2025-06-15',
            'transplant_window_days' => 7,
            'germination_days_min' => 4,
            'germination_days_max' => 14,
            'germination_temp_min' => 60.0,
            'germination_temp_max' => 95.0,
            'germination_temp_optimal' => 80.0,
            'planting_depth_inches' => 0.5,
            'seed_spacing_inches' => 2.0,
            'row_spacing_inches' => 24.0,
            'seeds_per_hole' => 1,
            'requires_light_for_germination' => false,
            'transplant_soil_temp_min' => 60.0,
            'transplant_soil_temp_max' => 85.0,
            'hardening_off_days' => 7,
            'seed_type' => 'raw',
            'seed_starting_notes' => 'Fruiting crops need warm soil and consistent moisture for germination.',
            'transplant_notes' => 'Handle seedlings carefully to avoid damaging roots. Plant deeply to encourage strong root growth.',
            'hardening_off_notes' => 'Fruiting crops are tender and need careful hardening off to prevent transplant shock.'
        ];
    }

    /**
     * Get herb seeding data
     */
    private function getHerbSeedingData($season, $maturityDays)
    {
        return [
            'indoor_seed_start' => '2025-02-01',
            'indoor_seed_end' => '2025-05-01',
            'outdoor_seed_start' => '2025-03-15',
            'outdoor_seed_end' => '2025-06-01',
            'transplant_start' => '2025-04-01',
            'transplant_end' => '2025-06-15',
            'transplant_window_days' => 14,
            'germination_days_min' => 5,
            'germination_days_max' => 21,
            'germination_temp_min' => 50.0,
            'germination_temp_max' => 85.0,
            'germination_temp_optimal' => 70.0,
            'planting_depth_inches' => 0.125,
            'seed_spacing_inches' => 0.5,
            'row_spacing_inches' => 12.0,
            'seeds_per_hole' => 3,
            'requires_light_for_germination' => false,
            'transplant_soil_temp_min' => 45.0,
            'transplant_soil_temp_max' => 80.0,
            'hardening_off_days' => 5,
            'seed_type' => 'raw',
            'seed_starting_notes' => 'Many herbs have varying germination times. Keep soil consistently moist.',
            'transplant_notes' => 'Herbs can often be direct seeded or transplanted. Some herbs don\'t transplant well.',
            'hardening_off_notes' => 'Most herbs are fairly hardy and don\'t need extensive hardening off.'
        ];
    }

    /**
     * Get generic seeding data as fallback
     */
    private function getGenericSeedingData($season, $maturityDays)
    {
        return [
            'indoor_seed_start' => '2025-03-01',
            'indoor_seed_end' => '2025-04-15',
            'outdoor_seed_start' => '2025-04-01',
            'outdoor_seed_end' => '2025-05-15',
            'transplant_start' => '2025-04-15',
            'transplant_end' => '2025-05-30',
            'transplant_window_days' => 14,
            'germination_days_min' => 3,
            'germination_days_max' => 14,
            'germination_temp_min' => 50.0,
            'germination_temp_max' => 85.0,
            'germination_temp_optimal' => 70.0,
            'planting_depth_inches' => 0.25,
            'seed_spacing_inches' => 1.0,
            'row_spacing_inches' => 12.0,
            'seeds_per_hole' => 1,
            'requires_light_for_germination' => false,
            'transplant_soil_temp_min' => 50.0,
            'transplant_soil_temp_max' => 80.0,
            'hardening_off_days' => 7,
            'seed_type' => 'raw',
            'seed_starting_notes' => 'Start seeds indoors 4-6 weeks before transplanting outdoors.',
            'transplant_notes' => 'Transplant after danger of frost has passed.',
            'hardening_off_notes' => 'Gradually acclimate seedlings to outdoor conditions.'
        ];
    }

    /**
     * Apply crop-specific adjustments
     */
    private function applyCropSpecificAdjustments($cropName, $data)
    {
        // Special adjustments for specific crops
        if (str_contains($cropName, 'tomato')) {
            $data['planting_depth_inches'] = 0.75;
            $data['seed_spacing_inches'] = 2.0;
            $data['row_spacing_inches'] = 36.0;
            $data['transplant_notes'] = 'Plant tomatoes deeply to encourage strong root growth. Remove lower leaves when transplanting.';
        } elseif (str_contains($cropName, 'pepper')) {
            $data['germination_temp_optimal'] = 85.0;
            $data['seed_starting_notes'] = 'Peppers are slow to germinate. Maintain soil temperature above 80°F.';
        } elseif (str_contains($cropName, 'carrot')) {
            $data['planting_depth_inches'] = 0.125;
            $data['seed_spacing_inches'] = 0.5;
            $data['transplant_start'] = null;
            $data['transplant_end'] = null;
            $data['seed_starting_notes'] = 'Carrots must be direct seeded. Do not transplant.';
        } elseif (str_contains($cropName, 'lettuce')) {
            $data['requires_light_for_germination'] = true;
            $data['seed_starting_notes'] = 'Lettuce seeds need light to germinate. Do not cover seeds deeply.';
        } elseif (str_contains($cropName, 'radish')) {
            $data['germination_days_max'] = 7;
            $data['seed_starting_notes'] = 'Radishes germinate quickly and can be succession planted every 2 weeks.';
        }

        return $data;
    }
}
