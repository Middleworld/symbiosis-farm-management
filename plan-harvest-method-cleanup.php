<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "HARVEST METHOD DATA CLEANUP PLAN\n";
echo str_repeat('=', 100) . "\n\n";

// Define crop rules
$rules = [
    // SINGLE HARVEST - you harvest once and plant is done
    'single' => [
        'Cabbage' => 'Forms one head per plant',
        'Brussels' => 'Sprouts picked once when mature',
        'Cauliflower' => 'Single head formation',
        'Calabrese' => 'Main head harvest (though side shoots can follow)',
        'Sprouting' => 'Main harvest period, but could be continuous',
        'Potato' => 'Single harvest when foliage dies',
        'Turnip' => 'Root harvest once',
        'Kohl' => 'Single bulb formation',
        'Florence' => 'Single bulb harvest',
        'Chicory' => 'Single head/root harvest',
        'Celery' => 'Single plant harvest',
        'Broad' => 'One main harvest window',
        'Pea' => 'Depends: mange tout continuous, shelling peas single/few picks',
        'Onion' => 'Single bulb harvest',
        'Sweetcorn' => 'Each cob ripens once',
        'Artichoke' => 'Single head per stem (but perennial)',
    ],
    
    // CONTINUOUS HARVEST - pick repeatedly from same plant
    'continuous' => [
        'Courgette' => 'Pick small fruits continuously',
        'Marrow' => 'Can harvest continuously if picked young',
        'Squash' => 'Depends: summer squash continuous, winter single',
        'Cucumber' => 'Pick continuously',
        'Aubergine' => 'Multiple fruits per plant over season',
        'Pepper' => 'Multiple fruits per plant',
        'Chilli' => 'Multiple fruits per plant',
        'Runner' => 'Pick beans continuously',
        'French' => 'Pick beans continuously',
        'Asparagus' => 'Spears emerge continuously (perennial)',
        'Rhubarb' => 'Pull stalks continuously (perennial)',
        'Kale' => 'Pick leaves continuously',
        'Tomato' => 'Pick fruits continuously',
    ],
];

echo "ANALYSIS:\n";
echo str_repeat('-', 100) . "\n\n";

foreach ($rules as $should_be => $crops) {
    echo strtoupper($should_be) . " HARVEST crops:\n";
    foreach ($crops as $crop => $reason) {
        $count = \App\Models\PlantVariety::where('name', 'like', $crop . '%')
            ->where('harvest_method', 'continuous')
            ->count();
        
        if ($count > 0) {
            $correct = ($should_be === 'continuous');
            $status = $correct ? '✓ CORRECT' : '✗ NEEDS FIX';
            echo sprintf("  %-20s %3d varieties  %s  - %s\n", 
                $crop, $count, $status, $reason);
        }
    }
    echo "\n";
}

echo str_repeat('=', 100) . "\n";
echo "RECOMMENDATIONS:\n";
echo str_repeat('-', 100) . "\n";
echo "1. Change to SINGLE-HARVEST (71 varieties):\n";
echo "   - Cabbage (14), Brussels (19), Cauliflower (9), Calabrese (11)\n";
echo "   - Sprouting (18) - though PSB could be continuous?\n";
echo "   - Potato (2), Turnip (2), Kohl (2), Florence (3), Chicory (2)\n";
echo "   - Celery (1), Broad (2), Onion (1), Sweetcorn (1), Artichoke (1)\n\n";

echo "2. KEEP as CONTINUOUS (correct) - 95 varieties:\n";
echo "   - Courgette (15), Marrow (3), Squash (1), Cucumber (20)\n";
echo "   - Aubergine (16), Pepper (2), Chilli (1)\n";
echo "   - Runner (24), French (1)\n";
echo "   - Asparagus (11), Rhubarb (2)\n\n";

echo "3. REVIEW CASE-BY-CASE:\n";
echo "   - Pea (23): Mangetout = continuous, Shelling = single/multiple-passes\n";
echo "   - Sprouting (18): Purple Sprouting Broccoli = continuous side shoots\n";
echo "   - Calabrese (11): Main head = single, but produces side shoots\n\n";

echo "4. IGNORE (flowers - decorative, not production critical):\n";
echo "   - 1620+ ornamental varieties marked continuous\n\n";
