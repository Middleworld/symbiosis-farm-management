<?php

// Script to sync PlantVariety data from MySQL to PostgreSQL for RAG

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Starting PlantVariety data sync from MySQL to PostgreSQL...\n";

try {
    // Get all active plant varieties from MySQL
    $plantVarieties = DB::connection('mysql')->table('plant_varieties')
        ->where('is_active', true)
        ->orderBy('id')
        ->get();

    echo "Found " . count($plantVarieties) . " active plant varieties to sync.\n";

    $synced = 0;
    $skipped = 0;
    $errors = 0;

    foreach ($plantVarieties as $variety) {
        try {
            // Check if this variety already exists in PostgreSQL
            $exists = DB::connection('pgsql_rag')->table('plant_varieties')
                ->where('farmos_id', $variety->farmos_id)
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            // Prepare data for PostgreSQL (convert MySQL enum to string)
            $data = [
                'farmos_id' => $variety->farmos_id,
                'farmos_tid' => $variety->farmos_tid,
                'name' => $variety->name,
                'description' => $variety->description,
                'image_url' => $variety->image_url,
                'image_alt_text' => $variety->image_alt_text,
                'scientific_name' => $variety->scientific_name,
                'crop_family' => $variety->crop_family,
                'plant_type' => $variety->plant_type,
                'plant_type_id' => $variety->plant_type_id,
                'maturity_days' => $variety->maturity_days,
                'propagation_days' => $variety->propagation_days,
                'min_temperature' => $variety->min_temperature,
                'max_temperature' => $variety->max_temperature,
                'optimal_temperature' => $variety->optimal_temperature,
                'season' => $variety->season,
                'frost_tolerance' => $variety->frost_tolerance,
                'companions' => $variety->companions,
                'external_uris' => $variety->external_uris,
                'farmos_data' => $variety->farmos_data,
                'is_active' => (bool)$variety->is_active,
                'last_synced_at' => $variety->last_synced_at,
                'sync_status' => $variety->sync_status,
                'created_at' => $variety->created_at,
                'updated_at' => $variety->updated_at,
                'harvest_start' => $variety->harvest_start,
                'harvest_end' => $variety->harvest_end,
                'yield_peak' => $variety->yield_peak,
                'harvest_window_days' => $variety->harvest_window_days,
                'season_type' => $variety->season_type, // enum becomes string
                'harvest_notes' => $variety->harvest_notes,
                'harvest_method' => $variety->harvest_method,
                'expected_yield_per_plant' => $variety->expected_yield_per_plant,
                'yield_unit' => $variety->yield_unit,
                'seasonal_adjustments' => $variety->seasonal_adjustments,
                'indoor_seed_start' => $variety->indoor_seed_start,
                'indoor_seed_end' => $variety->indoor_seed_end,
                'outdoor_seed_start' => $variety->outdoor_seed_start,
                'outdoor_seed_end' => $variety->outdoor_seed_end,
                'transplant_start' => $variety->transplant_start,
                'transplant_end' => $variety->transplant_end,
                'transplant_window_days' => $variety->transplant_window_days,
                'germination_days_min' => $variety->germination_days_min,
                'germination_days_max' => $variety->germination_days_max,
                'germination_temp_min' => $variety->germination_temp_min,
                'germination_temp_max' => $variety->germination_temp_max,
                'germination_temp_optimal' => $variety->germination_temp_optimal,
                'planting_depth_inches' => $variety->planting_depth_inches,
                'seed_spacing_inches' => $variety->seed_spacing_inches,
                'row_spacing_inches' => $variety->row_spacing_inches,
                'in_row_spacing_cm' => $variety->in_row_spacing_cm,
                'between_row_spacing_cm' => $variety->between_row_spacing_cm,
                'planting_method' => $variety->planting_method, // enum becomes string
                'seeds_per_hole' => $variety->seeds_per_hole,
                'requires_light_for_germination' => (bool)$variety->requires_light_for_germination,
                'seed_starting_notes' => $variety->seed_starting_notes,
                'seed_type' => $variety->seed_type,
                'transplant_soil_temp_min' => $variety->transplant_soil_temp_min,
                'transplant_soil_temp_max' => $variety->transplant_soil_temp_max,
                'transplant_notes' => $variety->transplant_notes,
                'hardening_off_days' => $variety->hardening_off_days,
                'hardening_off_notes' => $variety->hardening_off_notes,
                'transplant_month_start' => $variety->transplant_month_start,
                'transplant_month_end' => $variety->transplant_month_end,
            ];

            // Insert into PostgreSQL
            DB::connection('pgsql_rag')->table('plant_varieties')->insert($data);
            $synced++;

            if ($synced % 50 == 0) {
                echo "Synced {$synced} varieties...\n";
            }

        } catch (Exception $e) {
            $errors++;
            echo "Error syncing variety {$variety->farmos_id}: " . $e->getMessage() . "\n";
            Log::error("PlantVariety sync error for {$variety->farmos_id}: " . $e->getMessage());
        }
    }

    echo "\nSync completed!\n";
    echo "Synced: {$synced}\n";
    echo "Skipped (already exist): {$skipped}\n";
    echo "Errors: {$errors}\n";

} catch (Exception $e) {
    echo "Fatal error: " . $e->getMessage() . "\n";
    Log::error("PlantVariety sync fatal error: " . $e->getMessage());
}