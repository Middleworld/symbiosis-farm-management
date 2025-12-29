#!/usr/bin/env php
<?php

/**
 * TEST: Audit just 5 succession crops for maturity_days
 * Copying EXACTLY how the working varieties:audit command does it
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PlantVariety;
use App\Models\VarietyAuditResult;
use App\Services\AI\SymbiosisAIService;

$ai = app(SymbiosisAIService::class);

// Get just 5 lettuce varieties for testing
$varieties = PlantVariety::where('name', 'LIKE', '%Lettuce%')
    ->whereNull('maturity_days')
    ->limit(5)
    ->get();

echo "🧪 TEST: Auditing " . $varieties->count() . " lettuce varieties\n\n";

$processed = 0;
$saved = 0;
$errors = 0;

foreach ($varieties as $variety) {
    echo "Processing: {$variety->name}... ";
    
    try {
        $prompt = "Analyze this plant variety and suggest maturity_days AND season_type.

VARIETY: {$variety->name}
CATEGORY: {$variety->category}

FIELDS TO SUGGEST:
1. maturityDays: days from planting to harvest (number only)
2. seasonType: classification based on maturity (MUST be one of: early, mid, late)

SEASON TYPE RULES:
- early: < 60 days to maturity
- mid: 60-75 days to maturity  
- late: > 75 days to maturity

RESPONSE FORMAT (JSON only):
{
  \"issues\": [\"Missing maturity days and season classification\"],
  \"suggestions\": {
    \"maturityDays\": 55,
    \"seasonType\": \"early\"
  },
  \"confidence\": \"high\",
  \"severity\": \"info\"
}

CRITICAL: seasonType MUST be exactly 'early', 'mid', or 'late' (no other values allowed)";

        $responseData = $ai->chat(
            [
                ['role' => 'system', 'content' => 'You are a horticultural data expert.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            [
                'provider' => 'anthropic',
                'model' => 'claude-sonnet-4-20250514'
            ]
        );

        $response = $responseData['choices'][0]['message']['content'] ?? '';

        // Parse JSON response
        $jsonMatch = [];
        if (preg_match('/\{[\s\S]*\}/', $response, $jsonMatch)) {
            $data = json_decode($jsonMatch[0], true);
            
            $suggestions = $data['suggestions'] ?? [];
            $issueDescription = implode('; ', $data['issues'] ?? ['Missing data']);
            $severity = $data['severity'] ?? 'info';
            $confidence = $data['confidence'] ?? 'medium';
            
            $savedCount = 0;
            
            // Save maturity_days if suggested
            if (isset($suggestions['maturityDays'])) {
                VarietyAuditResult::create([
                    'variety_id' => $variety->id,
                    'audit_run_id' => 'test-5-' . now()->format('Y-m-d-His'),
                    'issue_description' => $issueDescription,
                    'severity' => $severity,
                    'confidence' => $confidence,
                    'suggested_field' => 'maturity_days',
                    'current_value' => $variety->maturity_days,
                    'suggested_value' => $suggestions['maturityDays'],
                    'status' => 'pending',
                ]);
                $savedCount++;
            }
            
            // Save season_type if suggested
            if (isset($suggestions['seasonType'])) {
                VarietyAuditResult::create([
                    'variety_id' => $variety->id,
                    'audit_run_id' => 'test-5-' . now()->format('Y-m-d-His'),
                    'issue_description' => $issueDescription,
                    'severity' => $severity,
                    'confidence' => $confidence,
                    'suggested_field' => 'season_type',
                    'current_value' => $variety->season_type,
                    'suggested_value' => $suggestions['seasonType'],
                    'status' => 'pending',
                ]);
                $savedCount++;
            }
            
            if ($savedCount > 0) {
                echo "✅ Saved {$savedCount} suggestions (maturity: {$suggestions['maturityDays']}, season: {$suggestions['seasonType']})\n";
                $saved += $savedCount;
            } else {
                echo "⚠️ No suggestions returned\n";
            }
        } else {
            echo "❌ Failed to parse JSON\n";
            $errors++;
        }
        
        $processed++;
        
    } catch (\Exception $e) {
        $errors++;
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
    
    usleep(100000); // 100ms delay
}

echo "\n✅ COMPLETE!\n";
echo "Processed: {$processed}\n";
echo "Saved: {$saved}\n";
echo "Errors: {$errors}\n\n";

if ($saved > 0) {
    echo "✅ SUCCESS! Now check database:\n";
    echo "php artisan tinker --execute=\"echo \\App\\Models\\VarietyAuditResult::where('audit_run_id', 'LIKE', 'test-5-%')->count() . ' saved';\"\n";
}
