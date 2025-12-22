<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "PHASE 1: HARVEST METHOD CLEANUP - Safe Bulk Fixes\n";
echo str_repeat('=', 100) . "\n\n";

// Crops that are DEFINITELY single-harvest
$singleHarvestCrops = [
    'Cabbage',
    'Brussels',
    'Cauliflower',
    'Potato',
    'Turnip',
    'Kohl',
    'Florence',
    'Chicory',
    'Celery',
    'Broad',
    'Onion',
    'Sweetcorn',
    'Artichoke',
];

echo "Will change from 'continuous' to 'single-harvest':\n";
echo str_repeat('-', 100) . "\n\n";

$totalUpdated = 0;

foreach ($singleHarvestCrops as $crop) {
    $varieties = \App\Models\PlantVariety::where('name', 'like', $crop . '%')
        ->where('harvest_method', 'continuous')
        ->get();
    
    if ($varieties->count() > 0) {
        echo "$crop ({$varieties->count()} varieties):\n";
        
        foreach ($varieties as $variety) {
            echo "  - {$variety->name}\n";
            
            // Update the variety
            $variety->harvest_method = 'single-harvest';
            $variety->save();
            $totalUpdated++;
        }
        echo "\n";
    }
}

echo str_repeat('=', 100) . "\n";
echo "COMPLETED: Updated $totalUpdated varieties from 'continuous' to 'single-harvest'\n\n";

echo "NEXT STEPS:\n";
echo "1. Push these changes to FarmOS: php artisan farmos:push-varieties\n";
echo "2. Review Phase 2 crops (Pea, Sprouting, Calabrese) individually\n";
