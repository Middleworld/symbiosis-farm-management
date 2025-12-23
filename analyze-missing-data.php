<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$varieties = \App\Models\PlantVariety::whereNotNull('farmos_id')
    ->where('is_active', true)
    ->get();

// Get crop names from variety names (first word typically)
$cropStats = [];

foreach ($varieties as $variety) {
    $nameParts = explode(' ', $variety->name);
    $crop = $nameParts[0];
    
    if (!isset($cropStats[$crop])) {
        $cropStats[$crop] = [
            'missing_frost' => 0,
            'missing_transplant' => 0,
            'total' => 0
        ];
    }
    
    $cropStats[$crop]['total']++;
    
    if (empty($variety->frost_tolerance)) {
        $cropStats[$crop]['missing_frost']++;
    }
    
    if (empty($variety->transplant_days) || $variety->transplant_days == 0) {
        $cropStats[$crop]['missing_transplant']++;
    }
}

// Sort by crops with most missing data
uasort($cropStats, function($a, $b) {
    $aMissing = $a['missing_frost'] + $a['missing_transplant'];
    $bMissing = $b['missing_frost'] + $b['missing_transplant'];
    return $bMissing - $aMissing;
});

echo "TOP 30 Crops with Missing Frost Tolerance or Transplant Days:\n";
echo str_repeat('=', 85) . "\n";
echo sprintf("%-30s %8s %15s %15s\n", 
    'Crop', 'Total', 'Missing Frost', 'Missing Trans');
echo str_repeat('-', 85) . "\n";

$count = 0;
foreach ($cropStats as $crop => $stats) {
    if ($count++ >= 30) break;
    
    // Skip crops with no missing data
    if ($stats['missing_frost'] == 0 && $stats['missing_transplant'] == 0) continue;
    
    echo sprintf("%-30s %8d %15d %15d\n",
        $crop,
        $stats['total'],
        $stats['missing_frost'],
        $stats['missing_transplant']
    );
}

echo "\nSummary:\n";
echo "Total varieties analyzed: " . $varieties->count() . "\n";
echo "Total missing frost_tolerance: " . $varieties->filter(fn($v) => empty($v->frost_tolerance))->count() . "\n";
echo "Total missing transplant_days: " . $varieties->filter(fn($v) => empty($v->transplant_days) || $v->transplant_days == 0)->count() . "\n";
