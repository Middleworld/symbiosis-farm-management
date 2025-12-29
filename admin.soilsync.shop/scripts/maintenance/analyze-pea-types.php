<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "PEA VARIETIES - What Info Do We Need?\n";
echo str_repeat('=', 100) . "\n\n";

$peas = \App\Models\PlantVariety::where('name', 'like', 'Pea%')
    ->where('is_active', true)
    ->orderBy('name')
    ->get();

echo "The KEY question: Which peas are for PODS (mangetout/sugar snap) vs PEAS (shelling)?\n\n";
echo sprintf("%-60s %-20s %s\n", 'Variety Name', 'Current Method', 'Type Needed');
echo str_repeat('-', 100) . "\n";

foreach ($peas as $pea) {
    $name_lower = strtolower($pea->name);
    $type_guess = 'UNKNOWN - Need to research';
    
    // Try to guess from name
    if (strpos($name_lower, 'sugar') !== false || strpos($name_lower, 'oregon sugar') !== false) {
        $type_guess = 'MANGETOUT (eat pod) = continuous';
    } elseif (strpos($name_lower, 'mangetout') !== false) {
        $type_guess = 'MANGETOUT (eat pod) = continuous';
    } elseif (strpos($name_lower, 'snap') !== false) {
        $type_guess = 'SUGAR SNAP (eat pod) = continuous';
    } else {
        $type_guess = 'Probably SHELLING PEA (shell for peas) = single or multiple-passes';
    }
    
    echo sprintf("%-60s %-20s %s\n", 
        $pea->name, 
        $pea->harvest_method ?? 'NULL',
        $type_guess
    );
}

echo "\n" . str_repeat('=', 100) . "\n";
echo "WHAT WE NEED TO KNOW:\n";
echo str_repeat('-', 100) . "\n";
echo "For each pea variety, we need to identify:\n\n";
echo "1. MANGETOUT / SUGAR SNAP PEAS:\n";
echo "   - You eat the whole pod\n";
echo "   - Pick repeatedly over several weeks\n";
echo "   - Harvest method: CONTINUOUS\n\n";

echo "2. SHELLING PEAS (Petit Pois, Garden Peas):\n";
echo "   - You shell them for the peas inside\n";
echo "   - Usually 1-2 main harvests\n";
echo "   - Harvest method: SINGLE-HARVEST or MULTIPLE-PASSES\n\n";

echo "SOURCES TO CHECK:\n";
echo "  - Seed packet descriptions\n";
echo "  - Supplier catalogues (Kings Seeds, Marshalls, etc.)\n";
echo "  - Your own farming experience with these varieties\n";
