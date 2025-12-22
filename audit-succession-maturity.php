#!/usr/bin/env php
<?php

/**
 * Targeted audit for maturity_days on 302 succession crops
 * Cost: ~$2.60 (vs $35 for full audit)
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PlantVariety;
use App\Models\VarietyAuditResult;
use App\Services\AI\SymbiosisAIService;

$ai = app(SymbiosisAIService::class);

// Get the 302 succession crops missing maturity_days
$successionCrops = ['Brussels', 'Cabbage', 'Broccoli', 'Cauliflower', 'Lettuce', 'Kale', 'Bean', 'Pea', 'Carrot'];

$varieties = PlantVariety::where(function($q) use ($successionCrops) {
    foreach ($successionCrops as $crop) {
        $q->orWhere('name', 'LIKE', '%' . $crop . '%');
    }
})->whereNull('maturity_days')->get();

echo "🎯 Targeted Maturity Days Audit\n";
echo "================================\n\n";
echo "Succession crops needing maturity_days: " . $varieties->count() . "\n";
echo "Estimated cost: ~$2.60\n\n";

$bar = new \Symfony\Component\Console\Helper\ProgressBar(
    new \Symfony\Component\Console\Output\ConsoleOutput(), 
    $varieties->count()
);
$bar->setFormat('%current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s% - %message%');
$bar->setMessage('Starting...');
$bar->start();

$processed = 0;
$suggestions = 0;
$errors = 0;

foreach ($varieties as $variety) {
    $bar->setMessage($variety->name);
    
    try {
        // Build focused prompt for maturity_days only
        $prompt = "You are a horticultural data expert. Analyze this plant variety and suggest ONLY the maturity_days field if it's missing or incorrect.

VARIETY: {$variety->name}

CURRENT DATA:
- Category: {$variety->category}
- Maturity Days: " . ($variety->maturity_days ?: 'MISSING') . "
" . ($variety->description ? "- Description: {$variety->description}\n" : "") . "

TASK: Research this specific variety and determine the correct maturity_days (days from planting to harvest).

CRITICAL FIELD NAME RULES:
- Field name MUST be exactly: maturityDays

RESPONSE FORMAT (JSON only, no other text):
{
  \"suggestions\": [
    {
      \"field\": \"maturityDays\",
      \"currentValue\": null,
      \"suggestedValue\": 75,
      \"reasoning\": \"Standard maturity for this lettuce variety\",
      \"confidence\": \"high\",
      \"severity\": \"medium\"
    }
  ]
}

If the variety already has correct maturity_days, return empty suggestions array.
Focus on ACCURACY - research the specific variety name for exact maturity timing.";

        $responseData = $ai->chat(
            [
                ['role' => 'system', 'content' => 'You are a horticultural data expert specializing in vegetable variety characteristics.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            [
                'provider' => 'anthropic',
                'model' => 'claude-sonnet-4-20250514'
            ]
        );

        $response = $responseData['choices'][0]['message']['content'] ?? '';

        // Parse response
        $jsonMatch = [];
        if (preg_match('/\{[\s\S]*"suggestions"[\s\S]*\}/', $response, $jsonMatch)) {
            $data = json_decode($jsonMatch[0], true);
            
            if (isset($data['suggestions']) && is_array($data['suggestions'])) {
                foreach ($data['suggestions'] as $suggestion) {
                    // Validate it's maturity_days
                    if ($suggestion['field'] !== 'maturityDays') {
                        continue;
                    }
                    
                    // Store in audit results
                    VarietyAuditResult::create([
                        'variety_id' => $variety->id,
                        'audit_run_id' => 'succession-maturity-' . now()->format('Y-m-d'),
                        'suggested_field' => $suggestion['field'],
                        'current_value' => $suggestion['currentValue'],
                        'suggested_value' => $suggestion['suggestedValue'],
                        'issue_description' => $suggestion['reasoning'] ?? 'Missing maturity days data',
                        'reasoning' => $suggestion['reasoning'],
                        'confidence' => $suggestion['confidence'] ?? 'medium',
                        'severity' => 'info', // Always 'info' for maturity_days suggestions
                        'status' => 'pending'
                    ]);
                    
                    $suggestions++;
                }
            }
        }
        
        $processed++;
        
    } catch (\Exception $e) {
        $errors++;
        echo "\n❌ Error on {$variety->name}: " . $e->getMessage() . "\n";
    }
    
    $bar->advance();
    usleep(100000); // 100ms delay to avoid rate limits
}

$bar->finish();
echo "\n\n";

echo "✅ COMPLETE!\n";
echo "================\n";
echo "Processed: {$processed}\n";
echo "Suggestions: {$suggestions}\n";
echo "Errors: {$errors}\n\n";

if ($suggestions > 0) {
    echo "💡 Next steps:\n";
    echo "1. Review suggestions in Settings → AI Variety Audit\n";
    echo "2. Apply approved suggestions\n";
    echo "3. Run season_type audit (will be free - just calculations!)\n";
}
