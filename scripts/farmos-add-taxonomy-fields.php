#!/usr/bin/env php
<?php

/**
 * Drush script to add taxonomy fields to FarmOS plant_type vocabulary
 * 
 * Usage (run on FarmOS server):
 *   drush scr /path/to/this/script.php
 * 
 * Or via SSH:
 *   ssh user@farmos-server "cd /var/www/farmos && drush scr /path/to/this/script.php"
 */

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;

// Define all required fields
$fields = [
    // Core planting fields
    [
        'name' => 'maturity_days',
        'label' => 'Maturity Days',
        'type' => 'integer',
        'description' => 'Days from seeding to harvest maturity',
        'required' => false,
    ],
    [
        'name' => 'transplant_days',
        'label' => 'Transplant Days',
        'type' => 'integer',
        'description' => 'Days from seeding to transplant',
        'required' => false,
    ],
    [
        'name' => 'harvest_window_days',
        'label' => 'Harvest Window Days',
        'type' => 'integer',
        'description' => 'Number of days the crop can be harvested',
        'required' => false,
    ],
    
    // Season and timing
    [
        'name' => 'season_type',
        'label' => 'Season Type',
        'type' => 'list_string',
        'description' => 'Variety season classification for succession planting',
        'required' => false,
        'allowed_values' => [
            'early' => 'Early Season',
            'mid' => 'Mid Season',
            'late' => 'Late Season',
            'all_season' => 'All Season',
        ],
    ],
    [
        'name' => 'harvest_start_month',
        'label' => 'Harvest Start Month',
        'type' => 'integer',
        'description' => 'Month (1-12) when harvest typically begins',
        'required' => false,
    ],
    [
        'name' => 'harvest_end_month',
        'label' => 'Harvest End Month',
        'type' => 'integer',
        'description' => 'Month (1-12) when harvest typically ends',
        'required' => false,
    ],
    
    // Germination
    [
        'name' => 'germination_days_min',
        'label' => 'Germination Days (Min)',
        'type' => 'integer',
        'description' => 'Minimum days to germination',
        'required' => false,
    ],
    [
        'name' => 'germination_days_max',
        'label' => 'Germination Days (Max)',
        'type' => 'integer',
        'description' => 'Maximum days to germination',
        'required' => false,
    ],
    [
        'name' => 'germination_temp_min',
        'label' => 'Germination Temp Min (°F)',
        'type' => 'decimal',
        'description' => 'Minimum temperature for germination',
        'required' => false,
        'precision' => 5,
        'scale' => 1,
    ],
    [
        'name' => 'germination_temp_max',
        'label' => 'Germination Temp Max (°F)',
        'type' => 'decimal',
        'description' => 'Maximum temperature for germination',
        'required' => false,
        'precision' => 5,
        'scale' => 1,
    ],
    [
        'name' => 'germination_temp_optimal',
        'label' => 'Germination Temp Optimal (°F)',
        'type' => 'decimal',
        'description' => 'Optimal temperature for germination',
        'required' => false,
        'precision' => 5,
        'scale' => 1,
    ],
    
    // Planting method
    [
        'name' => 'planting_method',
        'label' => 'Planting Method',
        'type' => 'list_string',
        'description' => 'How this variety is typically planted',
        'required' => false,
        'allowed_values' => [
            'direct' => 'Direct Seeded',
            'transplant' => 'Transplanted',
            'both' => 'Both Methods',
        ],
    ],
    [
        'name' => 'planting_depth_inches',
        'label' => 'Planting Depth (inches)',
        'type' => 'decimal',
        'description' => 'Seed planting depth',
        'required' => false,
        'precision' => 4,
        'scale' => 2,
    ],
    
    // Temperature tolerance
    [
        'name' => 'frost_tolerance',
        'label' => 'Frost Tolerance',
        'type' => 'list_string',
        'description' => 'Frost and cold tolerance level',
        'required' => false,
        'allowed_values' => [
            'hardy' => 'Hardy (tolerates frost)',
            'half_hardy' => 'Half Hardy (light frost)',
            'tender' => 'Tender (no frost)',
        ],
    ],
    [
        'name' => 'heat_tolerance',
        'label' => 'Heat Tolerance',
        'type' => 'list_string',
        'description' => 'Heat tolerance level',
        'required' => false,
        'allowed_values' => [
            'low' => 'Low',
            'medium' => 'Medium',
            'high' => 'High',
        ],
    ],
    
    // Spacing (already exist but included for completeness)
    [
        'name' => 'seed_spacing_cm',
        'label' => 'Seed Spacing (cm)',
        'type' => 'decimal',
        'description' => 'Spacing between seeds when direct seeding',
        'required' => false,
        'precision' => 5,
        'scale' => 1,
    ],
    [
        'name' => 'row_spacing_cm',
        'label' => 'Row Spacing (cm)',
        'type' => 'decimal',
        'description' => 'Spacing between rows',
        'required' => false,
        'precision' => 5,
        'scale' => 1,
    ],
    
    // Harvest
    [
        'name' => 'harvest_method',
        'label' => 'Harvest Method',
        'type' => 'list_string',
        'description' => 'How the crop is harvested',
        'required' => false,
        'allowed_values' => [
            'once' => 'Single Harvest',
            'cut_again' => 'Cut and Come Again',
            'continuous' => 'Continuous Harvest',
        ],
    ],
    
    // Growing preferences
    [
        'name' => 'light_preference',
        'label' => 'Light Preference',
        'type' => 'list_string',
        'description' => 'Sunlight requirements',
        'required' => false,
        'allowed_values' => [
            'full_sun' => 'Full Sun',
            'partial_shade' => 'Partial Shade',
            'shade' => 'Shade',
        ],
    ],
    [
        'name' => 'water_needs',
        'label' => 'Water Needs',
        'type' => 'list_string',
        'description' => 'Water requirements',
        'required' => false,
        'allowed_values' => [
            'low' => 'Low',
            'medium' => 'Medium',
            'high' => 'High',
        ],
    ],
];

echo "🌱 Adding taxonomy fields to plant_type vocabulary...\n\n";

$taxonomy_id = 'plant_type';
$added = 0;
$skipped = 0;
$errors = 0;

foreach ($fields as $field_def) {
    $field_name = 'field_' . $field_def['name'];
    
    try {
        // Check if field storage already exists
        $field_storage = FieldStorageConfig::loadByName('taxonomy_term', $field_name);
        
        if (!$field_storage) {
            // Create field storage
            $storage_config = [
                'field_name' => $field_name,
                'entity_type' => 'taxonomy_term',
                'type' => $field_def['type'],
                'cardinality' => 1,
            ];
            
            // Add type-specific settings
            if ($field_def['type'] === 'decimal') {
                $storage_config['settings'] = [
                    'precision' => $field_def['precision'] ?? 10,
                    'scale' => $field_def['scale'] ?? 2,
                ];
            } elseif ($field_def['type'] === 'list_string' && isset($field_def['allowed_values'])) {
                $storage_config['settings'] = [
                    'allowed_values' => $field_def['allowed_values'],
                ];
            }
            
            $field_storage = FieldStorageConfig::create($storage_config);
            $field_storage->save();
            
            echo "  ✅ Created field storage: {$field_name}\n";
        } else {
            echo "  ⏭️  Field storage exists: {$field_name}\n";
        }
        
        // Check if field instance exists for this taxonomy
        $field = FieldConfig::loadByName('taxonomy_term', $taxonomy_id, $field_name);
        
        if (!$field) {
            // Create field instance
            $field = FieldConfig::create([
                'field_storage' => $field_storage,
                'bundle' => $taxonomy_id,
                'label' => $field_def['label'],
                'description' => $field_def['description'] ?? '',
                'required' => $field_def['required'] ?? false,
            ]);
            $field->save();
            
            echo "     ✅ Added to {$taxonomy_id}: {$field_def['label']}\n";
            $added++;
        } else {
            echo "     ⏭️  Already exists: {$field_def['label']}\n";
            $skipped++;
        }
        
    } catch (\Exception $e) {
        echo "     ❌ Error with {$field_name}: " . $e->getMessage() . "\n";
        $errors++;
    }
    
    echo "\n";
}

echo "\n📊 Summary:\n";
echo "   ✅ Added: {$added}\n";
echo "   ⏭️  Skipped: {$skipped}\n";
echo "   ❌ Errors: {$errors}\n";
echo "\n";

if ($added > 0) {
    echo "✨ Fields successfully added! You can now:\n";
    echo "   1. Clear Drupal cache: drush cr\n";
    echo "   2. Push variety data from your local database\n";
    echo "   3. Test the succession planner with complete variety info\n";
} else {
    echo "ℹ️  All fields already exist. Ready to sync varieties!\n";
}
