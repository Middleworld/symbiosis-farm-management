<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "PHASE 2C: CALABRESE vs BROCCOLI Harvest Methods\n";
echo str_repeat('=', 100) . "\n\n";

echo "CALABRESE (single-harvest - side shoots not worth the effort):\n";
echo str_repeat('-', 100) . "\n";

$calabrese = \App\Models\PlantVariety::where('name', 'like', 'Calabrese%')
    ->where('harvest_method', 'continuous')
    ->get();

$calabrese_count = 0;
foreach ($calabrese as $cal) {
    $old = $cal->harvest_method;
    $cal->harvest_method = 'single-harvest';
    $cal->save();
    echo "  ✓ {$cal->name} (updated from '{$old}')\n";
    $calabrese_count++;
}

echo "\n" . str_repeat('=', 100) . "\n";
echo "BROCCOLI (continuous - side shoots are valuable):\n";
echo str_repeat('-', 100) . "\n";

// Check what broccoli varieties we have and their current methods
$broccoli = \App\Models\PlantVariety::where('name', 'like', 'Broccoli%')
    ->orWhere('name', 'like', '%Sprouting Broccoli%')
    ->get();

$broccoli_wrong = 0;
$broccoli_correct = 0;

foreach ($broccoli as $broc) {
    if ($broc->harvest_method !== 'continuous') {
        $old = $broc->harvest_method;
        $broc->harvest_method = 'continuous';
        $broc->save();
        echo "  ✓ {$broc->name} (updated from '{$old}' to 'continuous')\n";
        $broccoli_wrong++;
    } else {
        echo "  ✓ {$broc->name} (already correct: continuous)\n";
        $broccoli_correct++;
    }
}

echo "\n" . str_repeat('=', 100) . "\n";
echo "✅ COMPLETED:\n";
echo "  Calabrese updated: {$calabrese_count} → single-harvest\n";
echo "  Broccoli updated: {$broccoli_wrong} → continuous\n";
echo "  Broccoli already correct: {$broccoli_correct}\n";
