#!/usr/bin/env php
<?php
/**
 * Fix camelCase field names in existing audit results
 * 
 * The first audit stored field names using camelCase (maturityDays)
 * but the database uses snake_case (maturity_days). This script
 * updates all existing audit results to use the correct column names.
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$fieldMap = [
    'maturityDays' => 'maturity_days',
    'harvestDays' => 'harvest_days',
    'harvestWindowDays' => 'harvest_window_days',
    'inRowSpacing' => 'in_row_spacing_cm',
    'betweenRowSpacing' => 'between_row_spacing_cm',
    'plantingMethod' => 'planting_method',
    'description' => 'description',
    'harvestNotes' => 'harvest_notes',
    'seasonType' => 'season_type',
    'Plant Type' => 'plant_type',
    'Crop Family' => 'crop_family',
];

echo "🔧 Fixing audit field names...\n\n";

$results = App\Models\VarietyAuditResult::whereNotNull('suggested_field')->get();
$updated = 0;
$skipped = 0;

foreach ($results as $result) {
    $oldField = $result->suggested_field;
    
    if (isset($fieldMap[$oldField])) {
        $newField = $fieldMap[$oldField];
        $result->suggested_field = $newField;
        $result->save();
        $updated++;
        
        if ($updated % 100 == 0) {
            echo "Updated {$updated} field names...\n";
        }
    } else {
        $skipped++;
    }
}

echo "\n✅ Complete!\n";
echo "   Updated: {$updated} field names\n";
echo "   Skipped: {$skipped} (already correct or unknown fields)\n\n";
echo "Now you can apply the approved changes safely:\n";
echo "   Visit Settings Page → Apply All Approved Changes\n\n";
