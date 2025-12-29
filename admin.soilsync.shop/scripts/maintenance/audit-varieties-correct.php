#!/usr/bin/env php
<?php
/**
 * Audit varieties with CORRECT field mapping to prevent API waste
 * This version ensures field names match the actual database columns
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PlantVariety;
use App\Models\VarietyAuditResult;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

// Get the ACTUAL column names from the database
$actualColumns = DB::getSchemaBuilder()->getColumnListing('plant_varieties');
echo "📊 Database columns found: " . implode(', ', $actualColumns) . "\n\n";

// Map of what the AI might return to actual database columns
$fieldMapping = [
    // AI might return -> actual database column
    'maturityDays' => 'maturity_days',
    'maturity_days' => 'maturity_days',
    'Maturity Days' => 'maturity_days',
    'harvestDays' => 'harvest_window_days', // Map to harvest_window_days
    'harvest_days' => 'harvest_window_days',
    'Harvest Days' => 'harvest_window_days',
    'harvestWindowDays' => 'harvest_window_days',
    'harvest_window_days' => 'harvest_window_days',
    'Harvest Window Days' => 'harvest_window_days',
    'inRowSpacing' => 'in_row_spacing_cm',
    'in_row_spacing_cm' => 'in_row_spacing_cm',
    'In Row Spacing' => 'in_row_spacing_cm',
    'betweenRowSpacing' => 'between_row_spacing_cm',
    'between_row_spacing_cm' => 'between_row_spacing_cm',
    'Between Row Spacing' => 'between_row_spacing_cm',
    'plantingMethod' => 'planting_method',
    'planting_method' => 'planting_method',
    'Planting Method' => 'planting_method',
    'description' => 'description',
    'Description' => 'description',
    'harvestNotes' => 'harvest_notes',
    'harvest_notes' => 'harvest_notes',
    'Harvest Notes' => 'harvest_notes',
    'seasonType' => 'season_type',
    'season_type' => 'season_type',
    'Season Type' => 'season_type',
    'plantType' => 'plant_type',
    'plant_type' => 'plant_type',
    'Plant Type' => 'plant_type',
    'cropFamily' => 'crop_family',
    'crop_family' => 'crop_family',
    'Crop Family' => 'crop_family',
    // Additional mappings for fields that exist in DB
    'frost_tolerance' => 'frost_tolerance',
    'frostTolerance' => 'frost_tolerance',
    'Frost Tolerance' => 'frost_tolerance',
    'germination_temp_optimal' => 'germination_temp_optimal',
    'germinationTempOptimal' => 'germination_temp_optimal',
    'Germination Temp Optimal' => 'germination_temp_optimal',
    'transplant_window_days' => 'transplant_window_days',
    'transplantWindowDays' => 'transplant_window_days',
    'Transplant Window Days' => 'transplant_window_days',
    'transplant_days' => 'transplant_window_days', // Map incorrect field name
    'harvest_method' => 'harvest_method',
    'harvestMethod' => 'harvest_method',
    'Harvest Method' => 'harvest_method',
    'germination_days_min' => 'germination_days_min',
    'germinationDaysMin' => 'germination_days_min',
    'Germination Days Min' => 'germination_days_min',
    'germination_days_max' => 'germination_days_max',
    'germinationDaysMax' => 'germination_days_max',
    'Germination Days Max' => 'germination_days_max',
    'planting_depth_inches' => 'planting_depth_inches',
    'plantingDepthInches' => 'planting_depth_inches',
    'Planting Depth Inches' => 'planting_depth_inches',
];

// Function to normalize field name from AI response
function normalizeFieldName($aiFieldName) {
    global $fieldMapping, $actualColumns;

    // First try direct mapping
    if (isset($fieldMapping[$aiFieldName])) {
        $mapped = $fieldMapping[$aiFieldName];
        if (in_array($mapped, $actualColumns)) {
            return $mapped;
        }
    }

    // Try snake_case conversion
    $snakeCase = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $aiFieldName));
    if (in_array($snakeCase, $actualColumns)) {
        return $snakeCase;
    }

    // Try lowercase with underscores
    $lowercase = str_replace(' ', '_', strtolower($aiFieldName));
    if (in_array($lowercase, $actualColumns)) {
        return $lowercase;
    }

    // Log unmapped field for debugging
    echo "⚠️  WARNING: Could not map AI field '$aiFieldName' to database column\n";
    return null;
}

// Test mode first - verify field mapping without making API calls
echo "🧪 Testing field mapping (no API calls)...\n";
$testFields = array_keys($fieldMapping);
foreach ($testFields as $testField) {
    $mapped = normalizeFieldName($testField);
    if ($mapped) {
        echo "   ✓ '$testField' → '$mapped'\n";
    } else {
        echo "   ✗ '$testField' → NOT MAPPED\n";
    }
}

echo "\n";
$confirm = readline("Field mapping looks correct? Continue with audit? (y/n): ");
if (strtolower($confirm) !== 'y') {
    echo "Aborted.\n";
    exit(1);
}

// Get varieties that need auditing
$varieties = PlantVariety::whereNull('maturity_days')
    ->orWhereNull('harvest_window_days')
    ->orWhereNull('in_row_spacing_cm')
    ->orWhereNull('between_row_spacing_cm')
    ->orWhereNull('planting_method')
    ->orWhereNull('season_type')
    ->orWhereNull('frost_tolerance')
    ->limit(5) // Start with just 5 to test
    ->get();

if ($varieties->isEmpty()) {
    echo "✅ All varieties have complete data!\n";
    exit(0);
}

echo "🔍 Found " . $varieties->count() . " varieties needing audit\n\n";

$apiCalls = 0;
$maxApiCalls = 5; // Limit API calls to prevent waste

foreach ($varieties as $variety) {
    if ($apiCalls >= $maxApiCalls) {
        echo "\n⚠️  Reached API call limit of $maxApiCalls. Run again to continue.\n";
        break;
    }

    echo "🌱 Auditing: {$variety->variety_name} ({$variety->crop_name})\n";

    // Build prompt with explicit field name instructions
    $prompt = "For the vegetable variety '{$variety->variety_name}' of crop '{$variety->crop_name}', provide the following information.

    IMPORTANT: Return ONLY a JSON object with these EXACT field names (use snake_case):
    - maturity_days (integer: days from sowing/transplanting to first harvest)
    - harvest_window_days (integer: total harvest period in days)
    - in_row_spacing_cm (integer: spacing between plants in cm)
    - between_row_spacing_cm (integer: spacing between rows in cm)
    - planting_method (string: 'direct', 'transplant', 'both', or 'either')
    - season_type (string: 'early', 'mid', 'late', or 'all_season')
    - frost_tolerance (string: frost tolerance level)
    - description (string: brief variety description)
    - harvest_notes (string: harvesting tips)
    - plant_type (string: 'annual', 'biennial', or 'perennial')
    - crop_family (string: botanical family name)

    Return ONLY valid JSON, no explanatory text.";

    try {
        // Make API call (adjust endpoint as needed)
        $response = Http::timeout(30)->post('http://localhost:8006/api/variety-audit', [
            'prompt' => $prompt,
            'variety_id' => $variety->id
        ]);

        $apiCalls++;

        if ($response->successful()) {
            $data = $response->json();

            // Process and normalize each field
            foreach ($data as $aiField => $value) {
                $dbField = normalizeFieldName($aiField);

                if ($dbField && in_array($dbField, $actualColumns)) {
                    // Store audit result with CORRECT field name
                    VarietyAuditResult::create([
                        'plant_variety_id' => $variety->id,
                        'suggested_field' => $dbField, // Use normalized field name
                        'suggested_value' => $value,
                        'current_value' => $variety->$dbField,
                        'confidence' => 0.8,
                        'source' => 'ai_audit',
                        'approved' => false
                    ]);

                    echo "   ✓ {$dbField}: {$value}\n";
                } else {
                    echo "   ✗ Skipped unknown field: {$aiField}\n";
                }
            }
        } else {
            echo "   ❌ API error: " . $response->body() . "\n";
        }

    } catch (\Exception $e) {
        echo "   ❌ Error: " . $e->getMessage() . "\n";
    }

    // Rate limiting
    sleep(2);
}

echo "\n📊 Summary:\n";
echo "   API calls made: {$apiCalls}\n";
echo "   Varieties audited: " . min($apiCalls, $varieties->count()) . "\n";
echo "\n✅ Audit complete! Review results at /admin/settings\n";