<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FarmOSApi;
use Illuminate\Support\Facades\Log;

class CheckFarmOSTaxonomyFields extends Command
{
    protected $signature = 'farmos:check-taxonomy-fields';
    protected $description = 'Check which fields exist on FarmOS plant_type taxonomy';

    public function handle()
    {
        $api = new FarmOSApi();
        
        $this->info('🔍 Checking FarmOS Plant Type Taxonomy Fields...');
        $this->newLine();
        
        try {
            // Get a sample plant type term to see what fields it has
            $terms = $api->fetchPaginatedData('/api/taxonomy_term/plant_type', ['page' => ['limit' => 1]]);
            
            if (empty($terms)) {
                $this->warn('⚠️  No plant type terms found in FarmOS');
                return 1;
            }
            
            $sampleTerm = $terms[0];
            $this->info('📋 Sample term: ' . ($sampleTerm['attributes']['name'] ?? 'Unknown'));
            $this->newLine();
            
            // Required fields from the setup document (without field_ prefix)
            $requiredFields = [
                'season_type' => 'Season Type (for succession planting)',
                'germination_days_min' => 'Germination Days Minimum',
                'germination_days_max' => 'Germination Days Maximum',
                'germination_temp_optimal' => 'Germination Temperature Optimal',
                'planting_depth_inches' => 'Planting Depth',
                'frost_tolerance' => 'Frost Tolerance',
                'harvest_method' => 'Harvest Method',
                'planting_method' => 'Planting Method',
                'maturity_days' => 'Maturity Days',
                'transplant_days' => 'Transplant Days',
                'harvest_window_days' => 'Harvest Window Days',
                'harvest_start_month' => 'Harvest Start Month',
                'harvest_end_month' => 'Harvest End Month',
                'in_row_spacing_cm' => 'In-Row Spacing',
                'between_row_spacing_cm' => 'Between-Row Spacing',
            ];
            
            $existingFields = [];
            $missingFields = [];
            
            foreach ($requiredFields as $fieldName => $description) {
                // Check if key exists in attributes (even if value is null/empty)
                if (array_key_exists($fieldName, $sampleTerm['attributes'])) {
                    $existingFields[$fieldName] = $description;
                    $value = $sampleTerm['attributes'][$fieldName];
                    $valueDisplay = $value === null ? '(null)' : ($value === '' ? '(empty)' : substr((string)$value, 0, 20));
                    $this->line("✅ <fg=green>{$fieldName}</>: {$description} = {$valueDisplay}");
                } else {
                    $missingFields[$fieldName] = $description;
                    $this->line("❌ <fg=red>{$fieldName}</>: {$description}");
                }
            }
            
            $this->newLine();
            $this->info('📊 Summary:');
            $this->line('   Existing fields: ' . count($existingFields));
            $this->line('   Missing fields: ' . count($missingFields));
            
            if (!empty($missingFields)) {
                $this->newLine();
                $this->warn('⚠️  Missing Fields - Add these manually in FarmOS:');
                $this->newLine();
                $this->line('Go to: Structure → Taxonomy → Plant type → Manage fields');
                $this->newLine();
                
                foreach ($missingFields as $fieldName => $description) {
                    $this->line("   • {$fieldName}: {$description}");
                }
                
                $this->newLine();
                $this->info('📖 See FARMOS-VOCABULARY-SETUP.md for detailed instructions');
            } else {
                $this->newLine();
                $this->info('🎉 All required fields exist! Ready to sync varieties.');
            }
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            Log::error('Failed to check FarmOS taxonomy fields', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }
}
