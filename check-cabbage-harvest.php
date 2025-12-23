<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$cabbages = \App\Models\PlantVariety::where('name', 'like', 'Cabbage%')
    ->where('is_active', true)
    ->get(['name', 'harvest_method', 'maturity_days', 'harvest_window_days']);

echo "CABBAGE VARIETIES - Harvest Method Analysis:\n";
echo str_repeat('=', 90) . "\n";
echo sprintf("%-50s %-20s %10s %10s\n", 'Variety', 'Harvest Method', 'Maturity', 'Window');
echo str_repeat('-', 90) . "\n";

$methods = [];
foreach ($cabbages as $cab) {
    $method = $cab->harvest_method ?? 'NULL';
    if (!isset($methods[$method])) {
        $methods[$method] = 0;
    }
    $methods[$method]++;
    
    echo sprintf("%-50s %-20s %10d %10d\n", 
        substr($cab->name, 0, 50),
        $method,
        $cab->maturity_days ?? 0,
        $cab->harvest_window_days ?? 0
    );
}

echo str_repeat('=', 90) . "\n";
echo "Summary:\n";
foreach ($methods as $method => $count) {
    echo "  $method: $count varieties\n";
}

echo "\nNOTE: Cabbages are typically 'single-harvest' crops:\n";
echo "  - They form one head per plant\n";
echo "  - You harvest the entire head at once\n";
echo "  - Unlike lettuce or kale which can be cut-and-come-again\n";
echo "  - For succession, you plant NEW plants every 2-3 weeks\n";
