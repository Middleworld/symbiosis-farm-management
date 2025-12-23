<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "CABBAGE HARVEST METHOD ANALYSIS\n";
echo str_repeat('=', 100) . "\n\n";

$continuous = \App\Models\PlantVariety::where('name', 'like', 'Cabbage%')
    ->where('harvest_method', 'continuous')
    ->get(['name', 'maturity_days', 'harvest_window_days', 'notes']);

echo "14 CABBAGES MARKED AS 'CONTINUOUS':\n";
echo str_repeat('-', 100) . "\n";
echo sprintf("%-40s %10s %12s  Notes\n", 'Variety', 'Maturity', 'Window');
echo str_repeat('-', 100) . "\n";

$patterns = [
    'spring' => [],
    'pointed' => [],
    'winter_storage' => [],
    'savoy' => [],
    'other' => []
];

foreach ($continuous as $cab) {
    echo sprintf("%-40s %10d %12d  %s\n", 
        $cab->name,
        $cab->maturity_days,
        $cab->harvest_window_days,
        substr($cab->notes ?? '', 0, 40)
    );
    
    $name_lower = strtolower($cab->name);
    if (strpos($name_lower, 'spring') !== false) {
        $patterns['spring'][] = $cab->name;
    } elseif (strpos($name_lower, 'caraflex') !== false || strpos($name_lower, 'greyhound') !== false) {
        $patterns['pointed'][] = $cab->name;
    } elseif ($cab->maturity_days > 120) {
        $patterns['winter_storage'][] = $cab->name;
    } elseif (strpos($name_lower, 'savoy') !== false) {
        $patterns['savoy'][] = $cab->name;
    } else {
        $patterns['other'][] = $cab->name;
    }
}

echo "\n" . str_repeat('=', 100) . "\n";
echo "PATTERN ANALYSIS:\n";
echo str_repeat('-', 100) . "\n";

foreach ($patterns as $type => $varieties) {
    if (!empty($varieties)) {
        echo "\n" . strtoupper(str_replace('_', ' ', $type)) . " (" . count($varieties) . "):\n";
        foreach ($varieties as $v) {
            echo "  - $v\n";
        }
    }
}

echo "\n" . str_repeat('=', 100) . "\n";
echo "RESEARCH QUESTIONS TO ANSWER:\n";
echo str_repeat('-', 100) . "\n";
echo "1. Spring cabbages: Can be harvested as 'spring greens' before heading? (multi-cut)\n";
echo "2. Pointed types (Caraflex): Are these loose-leaf that regrow? Or single head?\n";
echo "3. Winter storage (Lodero, Stanton, Storka): Long window = continuous? Or just hardy?\n";
echo "4. F1 Magnus Cresco: Very early (65 days) - why continuous?\n";
echo "5. Should 'continuous' mean 'you can harvest leaves repeatedly'?\n";
echo "   OR 'you can harvest any time in a long window'?\n\n";

echo "RECOMMENDATION: Check seed catalogues for these specific varieties to understand\n";
echo "their actual harvest characteristics before making bulk changes.\n";
