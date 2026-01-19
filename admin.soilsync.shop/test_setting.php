<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo 'Testing Setting::get method: ';
$result = App\Models\Setting::get('company_number', '13617115');
echo $result . PHP_EOL;

echo 'Testing CompaniesHouseController instantiation: ';
try {
    $controller = app(App\Http\Controllers\Admin\CompaniesHouseController::class);
    echo 'Success!' . PHP_EOL;
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}