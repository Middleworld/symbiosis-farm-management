#!/usr/bin/env php
<?php

/**
 * Audit flower/ornamental varieties until credits run out
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PlantVariety;
use App\Models\VarietyAuditResult;
use App\Services\AI\SymbiosisAIService;

$ai = app(SymbiosisAIService::class);

// Get flower varieties (NOT succession crops)
$successionCrops = ['Brussels', 'Cabbage', 'Broccoli', 'Cauliflower', 'Lettuce', 'Kale', 'Bean', 'Pea', 'Carrot'];

$varieties = PlantVariety::where(function($q) use ($successionCrops) {
    foreach ($successionCrops as $crop) {
        $q->where('name', 'NOT LIKE', '%' . $crop . '%');
    }
})->whereNull('maturity_days')->get();

echo "🌸 Flower/Ornamental Audit (until credits run out)\n";
echo "==================================================\n\n";
echo "Varieties to audit: " . $varieties->count() . "\n";
echo "Balance: $8.54 (will run until depleted)\n\n";

$bar = new \Symfony\Component\Console\Helper\ProgressBar(
    new \Symfony\Component\Console\Output\ConsoleOutput(), 
    $varieties->count()
);
$bar->setFormat('%current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s% - %message%');
$bar->setMessage('Starting...');
$bar->start();

$processed = 0;
$totalSuggestions = 0;
$errors = 0;

foreach ($varieties as $variety) {
    $bar->setMessage($variety->name);
    
    try {
        $prompt = "You are a horticultural expert. Analyze this ornamental/flower variety and provide ALL missing production data.

VARIETY: {$variety->name}
CATEGORY: {$variety->category}

PROVIDE ALL OF THESE FIELDS (where applicable):
1. maturityDays: days from planting to flowering (number)
2. seasonType: 'early', 'mid', 'late', or 'all-season' for ornamentals
3. germinationDaysMin: minimum days to germinate (number)
4. germinationDaysMax: maximum days to germinate (number)
5. germinationTempOptimal: optimal germination temp in Celsius (number, e.g., 18)
6. plantingDepthInches: seed planting depth (decimal, e.g., 0.25)
7. frostTolerance: 'hardy', 'half-hardy', or 'tender'
8. harvestMethod: 'cut-and-come-again', 'single-harvest', or 'continuous'
9. transplantDays: days before transplanting (number, 0 if direct-sown)

CRITICAL: All numeric fields must be NUMBERS ONLY. Temperature in Celsius, not Fahrenheit.

RESPONSE (JSON only):
{
  \"issues\": [\"Missing production data\"],
  \"suggestions\": {
    \"maturityDays\": 60,
    \"seasonType\": \"all-season\",
    \"germinationDaysMin\": 7,
    \"germinationDaysMax\": 14,
    \"germinationTempOptimal\": 18,
    \"plantingDepthInches\": 0.25,
    \"frostTolerance\": \"half-hardy\",
    \"harvestMethod\": \"cut-and-come-again\",
    \"transplantDays\": 28
  },
  \"confidence\": \"high\",
  \"severity\": \"info\"
}";

        $responseData = $ai->chat(
            [
                ['role' => 'system', 'content' => 'You are a horticultural data expert. Respond ONLY with valid JSON.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            [
                'provider' => 'anthropic',
                'model' => 'claude-sonnet-4-20250514'
            ]
        );

        $response = $responseData['choices'][0]['message']['content'] ?? '';

        $jsonMatch = [];
        if (preg_match('/\{[\s\S]*\}/', $response, $jsonMatch)) {
            $data = json_decode($jsonMatch[0], true);
            
            if (!isset($data['suggestions'])) {
                throw new Exception('No suggestions in response');
            }
            
            $suggestions = $data['suggestions'];
            $issueDescription = implode('; ', $data['issues'] ?? ['Missing production data']);
            $severity = 'info';
            $confidence = $data['confidence'] ?? 'high';
            
            $fieldMap = [
                'maturityDays' => 'maturity_days',
                'seasonType' => 'season_type',
                'germinationDaysMin' => 'germination_days_min',
                'germinationDaysMax' => 'germination_days_max',
                'germinationTempOptimal' => 'germination_temp_optimal',
                'plantingDepthInches' => 'planting_depth_inches',
                'frostTolerance' => 'frost_tolerance',
                'harvestMethod' => 'harvest_method',
                'transplantDays' => 'transplant_days'
            ];
            
            $savedCount = 0;
            foreach ($suggestions as $camelField => $value) {
                if (!isset($fieldMap[$camelField])) {
                    continue;
                }
                
                $dbField = $fieldMap[$camelField];
                $currentValue = $variety->{$dbField};
                
                VarietyAuditResult::create([
                    'variety_id' => $variety->id,
                    'audit_run_id' => 'flowers-' . now()->format('Y-m-d'),
                    'issue_description' => $issueDescription,
                    'severity' => $severity,
                    'confidence' => $confidence,
                    'suggested_field' => $dbField,
                    'current_value' => $currentValue,
                    'suggested_value' => $value,
                    'status' => 'pending',
                ]);
                
                $savedCount++;
                $totalSuggestions++;
            }
        }
        
        $processed++;
        
    } catch (\Exception $e) {
        $errors++;
        
        // Check if credits ran out
        if (strpos($e->getMessage(), 'credit balance') !== false) {
            echo "\n\n💰 CREDITS DEPLETED! Stopping audit.\n";
            echo "Processed: {$processed}/{$varieties->count()}\n";
            echo "Suggestions saved: {$totalSuggestions}\n";
            break;
        }
        
        echo "\n❌ Error on {$variety->name}: " . $e->getMessage() . "\n";
    }
    
    $bar->advance();
    usleep(100000);
}

$bar->finish();
echo "\n\n";

echo "✅ COMPLETE (or credits ran out)!\n";
echo "=================================\n";
echo "Processed: {$processed}\n";
echo "Total suggestions: {$totalSuggestions}\n";
echo "Errors: {$errors}\n\n";

if ($totalSuggestions > 0) {
    echo "💡 Review in Settings → AI Variety Audit\n";
}
