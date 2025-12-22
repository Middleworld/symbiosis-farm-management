<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DeliveryCompletion;
use Carbon\Carbon;

class FixCompletionDates extends Command
{
    protected $signature = 'completions:fix-dates {--dry-run : Show changes without applying them}';
    protected $description = 'Fix completion dates after schedule change (Monday -> Thursday for deliveries, Friday/Saturday for collections)';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be saved');
        }
        
        $this->info('📊 Fixing completion dates to match new schedule...');
        $this->info('  Deliveries: Monday → Thursday (+3 days)');
        $this->info('  Collections: Keeping actual completion day (Friday/Saturday from completed_at)');
        $this->info('');
        
        // Get all completions with Monday delivery_date
        $completions = DeliveryCompletion::whereRaw('DAYOFWEEK(delivery_date) = 2')->get(); // Monday = 2
        
        $this->info("Found {$completions->count()} completions with Monday delivery_date");
        $this->info('');
        
        $deliveryCount = 0;
        $collectionCount = 0;
        
        foreach ($completions as $completion) {
            $oldDate = $completion->delivery_date->format('Y-m-d');
            $dayName = $completion->delivery_date->format('l');
            
            if ($completion->type === 'delivery') {
                // Deliveries: Move from Monday to Thursday (+3 days)
                $newDate = $completion->delivery_date->copy()->addDays(3);
                
                if ($dryRun) {
                    $this->line("  [DRY RUN] Delivery {$completion->external_id}: {$oldDate} (Monday) → {$newDate->format('Y-m-d')} (Thursday)");
                } else {
                    $completion->delivery_date = $newDate;
                    $completion->save();
                    $this->info("  ✓ Delivery {$completion->external_id}: {$oldDate} → {$newDate->format('Y-m-d')} (Thursday)");
                }
                $deliveryCount++;
                
            } else {
                // Collections: Use the actual day they came (from completed_at)
                $actualDay = Carbon::parse($completion->completed_at);
                $actualDayName = $actualDay->format('l');
                
                // Get the week start (Monday) from delivery_date
                $weekStart = $completion->delivery_date->copy();
                
                // Calculate Friday and Saturday of that week
                $friday = $weekStart->copy()->addDays(4); // Monday + 4 = Friday
                $saturday = $weekStart->copy()->addDays(5); // Monday + 5 = Saturday
                
                // Determine which day they actually came
                if ($actualDayName === 'Friday' || $actualDayName === 'Thursday') {
                    $newDate = $friday;
                } elseif ($actualDayName === 'Saturday' || $actualDayName === 'Sunday') {
                    $newDate = $saturday;
                } else {
                    // Default to Friday if completed on other days
                    $newDate = $friday;
                    $this->warn("  ⚠️  Collection {$completion->external_id} completed on {$actualDayName}, defaulting to Friday");
                }
                
                if ($dryRun) {
                    $this->line("  [DRY RUN] Collection {$completion->external_id}: {$oldDate} (Monday) → {$newDate->format('Y-m-d')} ({$newDate->format('l')}) [was completed on {$actualDayName}]");
                } else {
                    $completion->delivery_date = $newDate;
                    $completion->save();
                    $this->info("  ✓ Collection {$completion->external_id}: {$oldDate} → {$newDate->format('Y-m-d')} ({$newDate->format('l')})");
                }
                $collectionCount++;
            }
        }
        
        $this->info('');
        
        if ($dryRun) {
            $this->info("🔍 DRY RUN COMPLETE");
            $this->info("  Would update {$deliveryCount} deliveries (Monday → Thursday)");
            $this->info("  Would update {$collectionCount} collections (Monday → Friday/Saturday based on completed_at)");
        } else {
            $this->info("✅ Successfully updated completion dates:");
            $this->info("  {$deliveryCount} deliveries moved to Thursday");
            $this->info("  {$collectionCount} collections moved to Friday/Saturday");
        }
        
        return 0;
    }
}
