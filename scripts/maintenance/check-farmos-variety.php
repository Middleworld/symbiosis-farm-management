<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$api = new \App\Services\FarmOSApi();

// Fetch the specific term from FarmOS
$response = \Illuminate\Support\Facades\Http::withHeaders($api->getAuthHeaders())
    ->get(config('services.farmos.url') . '/api/taxonomy_term/plant_type/a28fca57-4e58-4c2a-8acb-44008200a578');

if ($response->successful()) {
    $term = $response->json();
    $attrs = $term['data']['attributes'];
    
    echo "FarmOS data for Beet Boltardy (graded):\n";
    echo "----------------------------------------\n";
    echo "season_type: " . var_export($attrs['season_type'] ?? null, true) . "\n";
    echo "harvest_start_month: " . var_export($attrs['harvest_start_month'] ?? null, true) . "\n";
    echo "harvest_end_month: " . var_export($attrs['harvest_end_month'] ?? null, true) . "\n";
    echo "maturity_days: " . var_export($attrs['maturity_days'] ?? null, true) . "\n";
    echo "transplant_days: " . var_export($attrs['transplant_days'] ?? null, true) . "\n";
    echo "harvest_window_days: " . var_export($attrs['harvest_window_days'] ?? null, true) . "\n";
} else {
    echo "Failed to fetch term: " . $response->status() . "\n";
    echo $response->body() . "\n";
}
