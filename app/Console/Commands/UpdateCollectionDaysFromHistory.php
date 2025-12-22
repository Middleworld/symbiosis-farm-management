<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DeliveryCompletion;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UpdateCollectionDaysFromHistory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'collections:update-preferred-days {--dry-run : Show changes without applying them}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze completion history and update preferred collection days for customers';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be saved');
        }
        
        $this->info('📊 Analyzing collection completion history...');
        
        // Get all collection completions, grouped by customer
        $completions = DeliveryCompletion::where('type', 'collection')
            ->whereNotNull('delivery_date')
            ->get();
        
        if ($completions->isEmpty()) {
            $this->warn('No collection completion history found.');
            return 0;
        }
        
        $this->info("Found {$completions->count()} collection completions");
        
        // Group by external_id (subscription ID) and analyze day patterns
        $customerPatterns = [];
        
        foreach ($completions as $completion) {
            $subscriptionId = $completion->external_id;
            // Use completed_at (actual day they came) instead of delivery_date (scheduled Monday)
            $dayOfWeek = Carbon::parse($completion->completed_at)->format('l'); // Monday, Tuesday, etc.
            
            // Convert Sunday to Saturday as per requirement
            if ($dayOfWeek === 'Sunday') {
                $dayOfWeek = 'Saturday';
            }
            
            // Only count Friday and Saturday (ignore other days)
            if (!in_array($dayOfWeek, ['Friday', 'Saturday'])) {
                continue;
            }
            
            if (!isset($customerPatterns[$subscriptionId])) {
                $customerPatterns[$subscriptionId] = [
                    'days' => [],
                    'customer_email' => $completion->customer_email,
                    'customer_name' => $completion->customer_name
                ];
            }
            
            // Count occurrences of each day
            if (!isset($customerPatterns[$subscriptionId]['days'][$dayOfWeek])) {
                $customerPatterns[$subscriptionId]['days'][$dayOfWeek] = 0;
            }
            $customerPatterns[$subscriptionId]['days'][$dayOfWeek]++;
        }
        
        $this->info('');
        $this->info('📋 Customer Collection Day Analysis:');
        $this->info('');
        
        $updates = [];
        
        foreach ($customerPatterns as $subscriptionId => $data) {
            // Find the most common day
            arsort($data['days']);
            $mostCommonDay = array_key_first($data['days']);
            $count = $data['days'][$mostCommonDay];
            $total = array_sum($data['days']);
            
            // Only update if it's Friday or Saturday (as per requirement)
            if (!in_array($mostCommonDay, ['Friday', 'Saturday'])) {
                $this->warn("  ⚠️  {$data['customer_name']}: Most common day is {$mostCommonDay} ({$count}/{$total}) - Setting to Friday (default)");
                $mostCommonDay = 'Friday';
            } else {
                $this->line("  ✓ {$data['customer_name']}: {$mostCommonDay} ({$count}/{$total} collections)");
            }
            
            $updates[$subscriptionId] = [
                'preferred_day' => $mostCommonDay,
                'customer_email' => $data['customer_email'],
                'customer_name' => $data['customer_name'],
                'confidence' => round(($count / $total) * 100, 1)
            ];
        }
        
        $this->info('');
        
        if (empty($updates)) {
            $this->warn('No updates to apply.');
            return 0;
        }
        
        // Now we need to map subscription IDs to customer IDs (WP user IDs)
        $this->info('🔍 Mapping subscription IDs to WordPress user IDs...');
        
        try {
            // Get customer_id from WordPress posts table
            $wpSubscriptions = DB::connection('wordpress')
                ->table('posts')
                ->join('postmeta', 'posts.ID', '=', 'postmeta.post_id')
                ->whereIn('posts.ID', array_keys($updates))
                ->where('posts.post_type', 'shop_subscription')
                ->where('postmeta.meta_key', '_customer_user')
                ->select('posts.ID as subscription_id', 'postmeta.meta_value as customer_id')
                ->get();
            
            $subscriptionToCustomer = [];
            foreach ($wpSubscriptions as $sub) {
                $subscriptionToCustomer[$sub->subscription_id] = $sub->customer_id;
            }
            
            $this->info("Mapped " . count($subscriptionToCustomer) . " subscriptions to customer IDs");
            
            // Now update WordPress user meta
            $updatedCount = 0;
            $skippedCount = 0;
            
            foreach ($updates as $subscriptionId => $updateData) {
                if (!isset($subscriptionToCustomer[$subscriptionId])) {
                    $this->warn("  ⚠️  Could not find customer ID for subscription {$subscriptionId} ({$updateData['customer_name']})");
                    $skippedCount++;
                    continue;
                }
                
                $customerId = $subscriptionToCustomer[$subscriptionId];
                $preferredDay = $updateData['preferred_day'];
                
                if ($dryRun) {
                    $this->line("  [DRY RUN] Would update customer {$customerId} ({$updateData['customer_name']}) to {$preferredDay}");
                } else {
                    // Update WordPress user meta
                    DB::connection('wordpress')
                        ->table('usermeta')
                        ->updateOrInsert(
                            [
                                'user_id' => $customerId,
                                'meta_key' => 'preferred_collection_day'
                            ],
                            [
                                'meta_value' => $preferredDay
                            ]
                        );
                    
                    $this->info("  ✓ Updated customer {$customerId} ({$updateData['customer_name']}) to {$preferredDay} (confidence: {$updateData['confidence']}%)");
                    $updatedCount++;
                }
            }
            
            $this->info('');
            
            if ($dryRun) {
                $this->info("🔍 DRY RUN COMPLETE - Would have updated {$updatedCount} customers");
            } else {
                $this->info("✅ Successfully updated {$updatedCount} customer collection day preferences");
            }
            
            if ($skippedCount > 0) {
                $this->warn("⚠️  Skipped {$skippedCount} updates due to missing customer mapping");
            }
            
        } catch (\Exception $e) {
            $this->error('❌ Error updating preferences: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
        
        return 0;
    }
}
