<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "PHASE 2: HARVEST METHOD REVIEW - Needs Farmer's Knowledge\n";
echo str_repeat('=', 100) . "\n\n";

echo "These crops need individual review because harvest method varies by type:\n\n";

// Peas - can be continuous (mangetout) or single/multiple (shelling)
echo "=" . str_repeat('=', 99) . "\n";
echo "PEAS (23 varieties) - Currently: continuous\n";
echo str_repeat('=', 100) . "\n";
echo "Question: Mangetout/Sugar snap = continuous | Shelling peas = single or multiple-passes?\n";
echo str_repeat('-', 100) . "\n";

$peas = \App\Models\PlantVariety::where('name', 'like', 'Pea%')
    ->where('harvest_method', 'continuous')
    ->get(['name', 'maturity_days']);

foreach ($peas as $pea) {
    $type = 'UNKNOWN';
    $name_lower = strtolower($pea->name);
    
    if (strpos($name_lower, 'mangetout') !== false || strpos($name_lower, 'sugar') !== false) {
        $type = '→ KEEP continuous (pick pods repeatedly)';
    } elseif (strpos($name_lower, 'shelling') !== false || strpos($name_lower, 'petit pois') !== false) {
        $type = '→ CHANGE to single or multiple-passes (pick once/twice)';
    } else {
        $type = '→ REVIEW: Not clear from name';
    }
    
    echo sprintf("  %-60s %s\n", $pea->name, $type);
}

// Sprouting - PSB harvested over weeks
echo "\n" . str_repeat('=', 100) . "\n";
echo "SPROUTING BROCCOLI (18 varieties) - Currently: continuous\n";
echo str_repeat('=', 100) . "\n";
echo "Question: Purple/White Sprouting produces side shoots over 6-8 weeks. Continuous or single?\n";
echo str_repeat('-', 100) . "\n";

$sprouting = \App\Models\PlantVariety::where('name', 'like', 'Sprouting%')
    ->where('harvest_method', 'continuous')
    ->get(['name', 'maturity_days']);

foreach ($sprouting as $sprout) {
    echo sprintf("  %-60s → Your call: Do you harvest repeatedly?\n", $sprout->name);
}

// Calabrese - main head single, but side shoots follow
echo "\n" . str_repeat('=', 100) . "\n";
echo "CALABRESE (11 varieties) - Currently: continuous\n";
echo str_repeat('=', 100) . "\n";
echo "Question: Main head is single harvest, but side shoots can be picked. What's your practice?\n";
echo str_repeat('-', 100) . "\n";

$calabrese = \App\Models\PlantVariety::where('name', 'like', 'Calabrese%')
    ->where('harvest_method', 'continuous')
    ->get(['name', 'maturity_days']);

foreach ($calabrese as $cal) {
    echo sprintf("  %-60s → Single (main head only) or continuous (with sides)?\n", $cal->name);
}

echo "\n" . str_repeat('=', 100) . "\n";
echo "SUMMARY:\n";
echo "  - Peas: 23 varieties to review (some continuous, some single/multiple-passes)\n";
echo "  - Sprouting: 18 varieties to decide (probably continuous if you pick over weeks)\n";
echo "  - Calabrese: 11 varieties to decide (single for main head, or continuous if picking sides)\n";
echo "  - TOTAL: 52 varieties need your farming expertise\n";
