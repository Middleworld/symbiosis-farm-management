<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "PHASE 2B: SPROUTING SEEDS (Microgreens) - Single Harvest\n";
echo str_repeat('=', 100) . "\n\n";
echo "Microgreens are cut once at 2-4 weeks, then crop is finished.\n";
echo "Changing from 'continuous' to 'single-harvest'\n\n";

$sprouting = \App\Models\PlantVariety::where('name', 'like', 'Sprouting Seed%')
    ->where('harvest_method', 'continuous')
    ->get();

echo "Updating {$sprouting->count()} varieties:\n";
echo str_repeat('-', 100) . "\n";

foreach ($sprouting as $sprout) {
    $old = $sprout->harvest_method;
    $sprout->harvest_method = 'single-harvest';
    $sprout->save();
    echo "  ✓ {$sprout->name}\n";
}

echo "\n" . str_repeat('=', 100) . "\n";
echo "✅ COMPLETED: {$sprouting->count()} sprouting seed varieties updated to 'single-harvest'\n";
echo "\nNOTE: Typical harvest times:\n";
echo "  - Most microgreens: 2 weeks (alfalfa, broccoli, kale, etc.)\n";
echo "  - Basil: 4 weeks\n";
echo "  - All are cut once, then finished\n";
