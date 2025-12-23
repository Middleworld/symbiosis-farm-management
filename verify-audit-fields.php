<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== FINAL FIELD VERIFICATION ===\n\n";

$columns = Illuminate\Support\Facades\Schema::getColumnListing('plant_varieties');

$fieldsInAudit = [
    'maturity_days',
    'harvest_window_days',
    'season_type',
    'in_row_spacing_cm',
    'between_row_spacing_cm',
    'germination_days_min',
    'germination_days_max',
    'germination_temp_optimal',
    'planting_depth_inches',
    'frost_tolerance',
    'harvest_method',
    'expected_yield_per_plant',
    'transplant_days',
    'description'
];

$allGood = true;
foreach($fieldsInAudit as $field) {
    $exists = in_array($field, $columns);
    $status = $exists ? '✅' : '❌ FAIL';
    echo "$status $field\n";
    if (!$exists) $allGood = false;
}

echo "\n";
if ($allGood) {
    echo "🎯 ALL 14 FIELDS VERIFIED - SAFE TO RUN \$5.70 AUDIT!\n";
    echo "✅ No wasted money - all fields exist in database\n";
} else {
    echo "🚨 FIX ERRORS BEFORE RUNNING AUDIT!\n";
}
