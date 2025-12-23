<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "PHASE 2A: PEA HARVEST METHOD CORRECTIONS\n";
echo str_repeat('=', 100) . "\n\n";

// Mangetout / Sugar Snap - CONTINUOUS (eat whole pod, pick repeatedly)
$continuous_peas = [
    'Pea Oregon Sugar Pod',
    'Pea Purple Magnolia',
    'Pea Sweet Horizon',
    'Pea Norli (organic)', // mangetout type despite name
];

// Shelling Peas - SINGLE-HARVEST (harvest once when pods fill)
$single_harvest_peas = [
    'Pea Alderman',
    'Pea Avola',
    'Pea Charlie',
    'Pea Delikett',
    'Pea Early Onward',
    'Pea Element',
    'Pea Hurst Greenshaft',
    'Pea Infinity',
    'Pea Jubilee',
    'Pea Karina (organic)',
    'Pea Kelvedon Wonder',
    'Pea Lusaka',
    'Pea Oasis',
    'Pea Onward',
    'Pea Petit Provencal',
    'Pea Progress No. 9',
    'Pea Progress No. 9 (organic)',
    'Pea Realm',
    'Pea Rondo (organic)',
];

echo "🟢 CONTINUOUS HARVEST (Mangetout/Sugar Snap - pick repeatedly):\n";
echo str_repeat('-', 100) . "\n";
$continuous_count = 0;
foreach ($continuous_peas as $name) {
    $pea = \App\Models\PlantVariety::where('name', $name)->first();
    if ($pea) {
        $old = $pea->harvest_method;
        $pea->harvest_method = 'continuous';
        $pea->save();
        $status = ($old === 'continuous') ? '✓ Already correct' : "✓ Updated from '$old'";
        echo "  {$name}: {$status}\n";
        $continuous_count++;
    } else {
        echo "  {$name}: ⚠️  NOT FOUND\n";
    }
}

echo "\n⚪ SINGLE HARVEST (Shelling peas - harvest once when pods fill):\n";
echo str_repeat('-', 100) . "\n";
$single_count = 0;
foreach ($single_harvest_peas as $name) {
    $pea = \App\Models\PlantVariety::where('name', $name)->first();
    if ($pea) {
        $old = $pea->harvest_method;
        $pea->harvest_method = 'single-harvest';
        $pea->save();
        $status = ($old === 'single-harvest') ? '✓ Already correct' : "✓ Updated from '$old'";
        echo "  {$name}: {$status}\n";
        $single_count++;
    } else {
        echo "  {$name}: ⚠️  NOT FOUND\n";
    }
}

// Check for any peas we missed
echo "\n" . str_repeat('=', 100) . "\n";
$all_classified = array_merge($continuous_peas, $single_harvest_peas);
$all_peas = \App\Models\PlantVariety::where('name', 'like', 'Pea%')
    ->where('is_active', true)
    ->pluck('name')
    ->toArray();

$missed = array_diff($all_peas, $all_classified);
if (count($missed) > 0) {
    echo "⚠️  UNCLASSIFIED PEAS (need review):\n";
    foreach ($missed as $pea) {
        echo "  - {$pea}\n";
    }
} else {
    echo "✅ ALL PEAS CLASSIFIED!\n";
}

echo "\nSUMMARY:\n";
echo "  Continuous harvest (mangetout/sugar snap): {$continuous_count}\n";
echo "  Single harvest (shelling peas): {$single_count}\n";
echo "  TOTAL: " . ($continuous_count + $single_count) . " peas updated\n";
