#!/bin/bash

# Auto-cleanup malformed variety audit entries
# Runs every 5 minutes via cron to fix spacing JSON and formatting issues

cd /opt/sites/admin.middleworldfarms.org

php artisan tinker <<'EOF'
// Fix JSON spacing entries
$jsonSpacing = \App\Models\VarietyAuditResult::where('suggested_field', 'spacing')
    ->where('suggested_value', 'like', '{%')
    ->get();

$fixed = 0;
foreach ($jsonSpacing as $entry) {
    $spacing = json_decode($entry->suggested_value, true);
    if (is_array($spacing) && isset($spacing['inRow']) && isset($spacing['betweenRow'])) {
        // Create inRowSpacing entry
        \App\Models\VarietyAuditResult::create([
            'variety_id' => $entry->variety_id,
            'suggested_field' => 'inRowSpacing',
            'current_value' => null,
            'suggested_value' => rtrim(str_replace(' cm', '', $spacing['inRow']), '.0'),
            'ai_reasoning' => $entry->ai_reasoning,
            'confidence_score' => $entry->confidence_score,
            'status' => 'pending'
        ]);
        
        // Create betweenRowSpacing entry
        \App\Models\VarietyAuditResult::create([
            'variety_id' => $entry->variety_id,
            'suggested_field' => 'betweenRowSpacing',
            'current_value' => null,
            'suggested_value' => rtrim(str_replace(' cm', '', $spacing['betweenRow']), '.0'),
            'ai_reasoning' => $entry->ai_reasoning,
            'confidence_score' => $entry->confidence_score,
            'status' => 'pending'
        ]);
        
        $entry->delete();
        $fixed++;
    }
}

echo "Fixed $fixed JSON spacing entries\n";

// Fix .0 decimals
$decimals = \App\Models\VarietyAuditResult::whereIn('suggested_field', [
    'maturityDays', 'harvestDays', 'inRowSpacing', 'betweenRowSpacing'
])->where('suggested_value', 'like', '%.0')->get();

foreach ($decimals as $entry) {
    $entry->suggested_value = rtrim($entry->suggested_value, '.0');
    $entry->save();
}

echo "Fixed " . count($decimals) . " decimal entries\n";

// Remove cm/days units
$units = \App\Models\VarietyAuditResult::where(function($q) {
    $q->where('suggested_value', 'like', '% cm')
      ->orWhere('suggested_value', 'like', '% days');
})->get();

foreach ($units as $entry) {
    $entry->suggested_value = trim(str_replace([' cm', ' days'], '', $entry->suggested_value));
    $entry->save();
}

echo "Fixed " . count($units) . " unit entries\n";

exit
EOF
