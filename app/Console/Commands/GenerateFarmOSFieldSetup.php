<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateFarmOSFieldSetup extends Command
{
    protected $signature = 'farmos:generate-field-setup';
    protected $description = 'Generate step-by-step instructions for adding FarmOS taxonomy fields';

    private $fields = [
        [
            'name' => 'maturity_days',
            'label' => 'Maturity Days',
            'type' => 'Number (integer)',
            'description' => 'Days from seeding to harvest maturity',
            'required' => false,
        ],
        [
            'name' => 'transplant_days',
            'label' => 'Transplant Days',
            'type' => 'Number (integer)',
            'description' => 'Days from seeding to transplant',
            'required' => false,
        ],
        [
            'name' => 'harvest_window_days',
            'label' => 'Harvest Window Days',
            'type' => 'Number (integer)',
            'description' => 'Number of days the crop can be harvested',
            'required' => false,
        ],
        [
            'name' => 'season_type',
            'label' => 'Season Type',
            'type' => 'List (text)',
            'description' => 'Variety season classification for succession planting',
            'required' => false,
            'allowed_values' => "early|Early Season\nmid|Mid Season\nlate|Late Season\nall_season|All Season",
        ],
        [
            'name' => 'harvest_start_month',
            'label' => 'Harvest Start Month',
            'type' => 'Number (integer)',
            'description' => 'Month (1-12) when harvest typically begins',
            'required' => false,
        ],
        [
            'name' => 'harvest_end_month',
            'label' => 'Harvest End Month',
            'type' => 'Number (integer)',
            'description' => 'Month (1-12) when harvest typically ends',
            'required' => false,
        ],
        [
            'name' => 'germination_days_min',
            'label' => 'Germination Days (Min)',
            'type' => 'Number (integer)',
            'description' => 'Minimum days to germination',
            'required' => false,
        ],
        [
            'name' => 'germination_days_max',
            'label' => 'Germination Days (Max)',
            'type' => 'Number (integer)',
            'description' => 'Maximum days to germination',
            'required' => false,
        ],
        [
            'name' => 'germination_temp_min',
            'label' => 'Germination Temp Min (°F)',
            'type' => 'Number (decimal)',
            'description' => 'Minimum temperature for germination in Fahrenheit',
            'required' => false,
            'precision' => 5,
            'scale' => 1,
        ],
        [
            'name' => 'germination_temp_max',
            'label' => 'Germination Temp Max (°F)',
            'type' => 'Number (decimal)',
            'description' => 'Maximum temperature for germination in Fahrenheit',
            'required' => false,
            'precision' => 5,
            'scale' => 1,
        ],
        [
            'name' => 'germination_temp_optimal',
            'label' => 'Germination Temp Optimal (°F)',
            'type' => 'Number (decimal)',
            'description' => 'Optimal temperature for germination in Fahrenheit',
            'required' => false,
            'precision' => 5,
            'scale' => 1,
        ],
        [
            'name' => 'planting_method',
            'label' => 'Planting Method',
            'type' => 'List (text)',
            'description' => 'How this variety is typically planted',
            'required' => false,
            'allowed_values' => "direct|Direct Seeded\ntransplant|Transplanted\nboth|Both Methods",
        ],
        [
            'name' => 'planting_depth_inches',
            'label' => 'Planting Depth (inches)',
            'type' => 'Number (decimal)',
            'description' => 'Seed planting depth in inches',
            'required' => false,
            'precision' => 4,
            'scale' => 2,
        ],
        [
            'name' => 'frost_tolerance',
            'label' => 'Frost Tolerance',
            'type' => 'List (text)',
            'description' => 'Frost and cold tolerance level',
            'required' => false,
            'allowed_values' => "hardy|Hardy (tolerates frost)\nhalf_hardy|Half Hardy (light frost)\ntender|Tender (no frost)",
        ],
        [
            'name' => 'heat_tolerance',
            'label' => 'Heat Tolerance',
            'type' => 'List (text)',
            'description' => 'Heat tolerance level',
            'required' => false,
            'allowed_values' => "low|Low\nmedium|Medium\nhigh|High",
        ],
        [
            'name' => 'harvest_method',
            'label' => 'Harvest Method',
            'type' => 'List (text)',
            'description' => 'How the crop is harvested',
            'required' => false,
            'allowed_values' => "once|Single Harvest\ncut_again|Cut and Come Again\ncontinuous|Continuous Harvest",
        ],
        [
            'name' => 'light_preference',
            'label' => 'Light Preference',
            'type' => 'List (text)',
            'description' => 'Sunlight requirements',
            'required' => false,
            'allowed_values' => "full_sun|Full Sun\npartial_shade|Partial Shade\nshade|Shade",
        ],
        [
            'name' => 'water_needs',
            'label' => 'Water Needs',
            'type' => 'List (text)',
            'description' => 'Water requirements',
            'required' => false,
            'allowed_values' => "low|Low\nmedium|Medium\nhigh|High",
        ],
    ];

    public function handle()
    {
        $this->info('🌱 FarmOS Taxonomy Field Setup Instructions');
        $this->newLine();
        
        $this->warn('📍 Navigate to your FarmOS instance:');
        $this->line('   Structure → Taxonomy → Plant type → Manage fields');
        $this->line('   Or go to: https://your-farmos-url/admin/structure/taxonomy/manage/plant_type/overview/fields');
        $this->newLine();
        
        $this->info('📋 Add the following ' . count($this->fields) . ' fields:');
        $this->newLine();
        
        foreach ($this->fields as $index => $field) {
            $num = $index + 1;
            $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->info("Field #{$num}: {$field['label']}");
            $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->newLine();
            
            $this->line("  1️⃣  Click '<fg=cyan>Add field</>' button");
            $this->line("  2️⃣  Select field type: <fg=yellow>{$field['type']}</>");
            $this->line("  3️⃣  Label: <fg=green>{$field['label']}</>");
            
            if (isset($field['allowed_values'])) {
                $this->newLine();
                $this->line("  4️⃣  Click 'Continue' then configure:");
                $this->line("      Allowed values (one per line):");
                $this->line("      <fg=cyan>┌────────────────────────────────────┐</>");
                foreach (explode("\n", $field['allowed_values']) as $value) {
                    $this->line("      <fg=cyan>│</> {$value}");
                }
                $this->line("      <fg=cyan>└────────────────────────────────────┘</>");
            } elseif (isset($field['precision'])) {
                $this->newLine();
                $this->line("  4️⃣  Click 'Continue' then configure:");
                $this->line("      Precision: <fg=yellow>{$field['precision']}</>");
                $this->line("      Scale: <fg=yellow>{$field['scale']}</>");
            }
            
            $this->newLine();
            $this->line("  📝 Description (copy this):");
            $this->line("      <fg=magenta>{$field['description']}</>");
            $this->line("  ✅ Required: <fg=yellow>" . ($field['required'] ? 'Yes' : 'No') . "</>");
            $this->line("  💾 Click '<fg=green>Save</>' then '<fg=green>Save settings</>'");
            $this->newLine();
            
            if ($num < count($this->fields)) {
                $this->line("  ⬇️  Repeat for next field...");
                $this->newLine();
            }
        }
        
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->newLine();
        
        $this->info('✨ After adding all fields:');
        $this->line('   1. Verify fields appear in the list');
        $this->line('   2. Run: php artisan farmos:check-taxonomy-fields');
        $this->line('   3. Push variety data: php artisan farmos:push-varieties');
        $this->newLine();
        
        $this->comment('💡 Tip: Open this terminal and your FarmOS admin panel side-by-side');
        $this->comment('    to copy-paste field settings as you go.');
        
        return 0;
    }
}
