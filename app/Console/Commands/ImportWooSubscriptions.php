<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\VegboxPlan;
use App\Models\VegboxSubscription;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportWooSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vegbox:import-woo-subscriptions 
                            {--dry-run : Show what would be imported without actually importing}
                            {--skip-renewals : Import subscriptions but mark them to skip automatic renewals}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'SAFELY import WooCommerce subscriptions into vegbox system (READ-ONLY from WooCommerce)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('╔════════════════════════════════════════════════════════════════════╗');
        $this->info('║     SAFE WooCommerce Subscription Import (Read-Only)              ║');
        $this->info('╚════════════════════════════════════════════════════════════════════╝');
        $this->newLine();

        $isDryRun = $this->option('dry-run');
        $skipRenewals = $this->option('skip-renewals');

        if ($isDryRun) {
            $this->warn('🔍 DRY RUN MODE - No data will be imported');
        }

        if ($skipRenewals) {
            $this->warn('⚠️  SKIP RENEWALS MODE - Imported subscriptions will NOT be auto-renewed');
            $this->warn('   (WooCommerce will continue handling renewals)');
        }

        $this->newLine();

        // Safety confirmation
        if (!$isDryRun && !$this->confirm('This will IMPORT (not modify) WooCommerce subscriptions. Continue?', true)) {
            $this->error('Import cancelled.');
            return 1;
        }

        $this->newLine();
        $this->info('📊 Analyzing WooCommerce subscriptions...');
        $this->newLine();

        // Get all WooCommerce subscriptions
        $wooSubscriptions = collect($this->getWooCommerceSubscriptions());

        if ($wooSubscriptions->isEmpty()) {
            $this->warn('No WooCommerce subscriptions found.');
            return 0;
        }

        $this->info("Found {$wooSubscriptions->count()} WooCommerce subscriptions");
        $this->newLine();

        // Ensure default plan exists
        $defaultPlan = $this->ensureDefaultPlan($isDryRun);

        $imported = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($wooSubscriptions as $wooSub) {
            $result = $this->importSubscription($wooSub, $defaultPlan, $isDryRun, $skipRenewals);
            
            if ($result === 'imported') {
                $imported++;
            } elseif ($result === 'skipped') {
                $skipped++;
            } else {
                $errors++;
            }
        }

        $this->newLine();
        $this->info('╔════════════════════════════════════════════════════════════════════╗');
        $this->info('║                      Import Summary                                ║');
        $this->info('╚════════════════════════════════════════════════════════════════════╝');
        $this->newLine();
        
        if ($isDryRun) {
            $this->line("  Would import: <fg=green>{$imported}</>");
        } else {
            $this->line("  ✅ Imported: <fg=green>{$imported}</>");
        }
        $this->line("  ⏭️  Skipped:  <fg=yellow>{$skipped}</>");
        $this->line("  ❌ Errors:   <fg=red>{$errors}</>");
        
        $this->newLine();
        
        if (!$isDryRun && $imported > 0) {
            $this->info('✅ Import complete! WooCommerce subscriptions were NOT modified.');
            if ($skipRenewals) {
                $this->warn('⚠️  Imported subscriptions will NOT be auto-renewed by Laravel.');
                $this->warn('   WooCommerce will continue handling renewals as before.');
            } else {
                $this->warn('⚠️  Imported subscriptions ARE eligible for Laravel auto-renewal.');
                $this->warn('   You may want to disable WooCommerce renewals to avoid duplicate charges!');
            }
        }

        return 0;
    }

    /**
     * Get all WooCommerce subscriptions from database
     */
    protected function getWooCommerceSubscriptions()
    {
        return DB::connection('wordpress')
            ->select('
                SELECT 
                    p.ID as subscription_id,
                    p.post_status,
                    p.post_date,
                    p.post_modified,
                    pm_customer.meta_value as customer_id,
                    pm_status.meta_value as subscription_status,
                    pm_start.meta_value as start_date,
                    pm_next.meta_value as next_payment_date,
                    pm_end.meta_value as end_date,
                    pm_trial_end.meta_value as trial_end_date,
                    pm_billing_period.meta_value as billing_period,
                    pm_billing_interval.meta_value as billing_interval,
                    pm_order_total.meta_value as order_total
                FROM D6sPMX_posts p
                LEFT JOIN D6sPMX_postmeta pm_customer ON p.ID = pm_customer.post_id AND pm_customer.meta_key = "_customer_user"
                LEFT JOIN D6sPMX_postmeta pm_status ON p.ID = pm_status.post_id AND pm_status.meta_key = "_status"
                LEFT JOIN D6sPMX_postmeta pm_start ON p.ID = pm_start.post_id AND pm_start.meta_key = "_schedule_start"
                LEFT JOIN D6sPMX_postmeta pm_next ON p.ID = pm_next.post_id AND pm_next.meta_key = "_schedule_next_payment"
                LEFT JOIN D6sPMX_postmeta pm_end ON p.ID = pm_end.post_id AND pm_end.meta_key = "_schedule_end"
                LEFT JOIN D6sPMX_postmeta pm_trial_end ON p.ID = pm_trial_end.post_id AND pm_trial_end.meta_key = "_schedule_trial_end"
                LEFT JOIN D6sPMX_postmeta pm_billing_period ON p.ID = pm_billing_period.post_id AND pm_billing_period.meta_key = "_billing_period"
                LEFT JOIN D6sPMX_postmeta pm_billing_interval ON p.ID = pm_billing_interval.post_id AND pm_billing_interval.meta_key = "_billing_interval"
                LEFT JOIN D6sPMX_postmeta pm_order_total ON p.ID = pm_order_total.post_id AND pm_order_total.meta_key = "_order_total"
                WHERE p.post_type = "shop_subscription"
                AND p.post_status IN ("wc-active", "wc-pending", "wc-on-hold", "wc-cancelled", "wc-expired")
                ORDER BY p.ID DESC
            ');
    }

    /**
     * Ensure default plan exists
     */
    protected function ensureDefaultPlan($isDryRun)
    {
        if ($isDryRun) {
            $this->line('  📦 Would ensure default plan exists');
            return null;
        }

        $plan = VegboxPlan::firstOrCreate(
            ['slug' => 'imported-from-woocommerce'],
            [
                'name' => 'Imported from WooCommerce',
                'description' => 'Placeholder plan for subscriptions imported from WooCommerce',
                'price' => 0.00,
                'currency' => 'GBP',
                'billing_interval' => 'month',
                'billing_period' => 1,
            ]
        );

        $this->line('  📦 Default plan ready: ' . $plan->name);
        
        return $plan;
    }

    /**
     * Import a single subscription
     */
    protected function importSubscription($wooSub, $defaultPlan, $isDryRun, $skipRenewals)
    {
        try {
            // Get user by WooCommerce customer ID
            $user = $this->findUserByWooCustomerId($wooSub->customer_id);
            
            if (!$user) {
                $this->warn("  ⏭️  Subscription #{$wooSub->subscription_id}: Customer ID {$wooSub->customer_id} not found in Laravel");
                return 'skipped';
            }

            // Check if already imported
            if (!$isDryRun) {
                $existing = VegboxSubscription::where('woo_subscription_id', $wooSub->subscription_id)->first();
                if ($existing) {
                    $this->line("  ⏭️  Subscription #{$wooSub->subscription_id}: Already imported (Vegbox #{$existing->id})");
                    return 'skipped';
                }
            }

            // Parse dates
            $startsAt = $wooSub->start_date ? Carbon::createFromTimestamp($wooSub->start_date) : Carbon::parse($wooSub->post_date);
            $nextBillingAt = $wooSub->next_payment_date ? Carbon::createFromTimestamp($wooSub->next_payment_date) : null;
            $endsAt = $wooSub->end_date ? Carbon::createFromTimestamp($wooSub->end_date) : null;
            $trialEndsAt = $wooSub->trial_end_date ? Carbon::createFromTimestamp($wooSub->trial_end_date) : null;

            // Determine status
            $status = $this->mapWooStatus($wooSub->subscription_status ?? $wooSub->post_status);
            
            // Only set canceled_at and ends_at if subscription is actually cancelled/expired in WooCommerce
            // For active subscriptions, leave these NULL so Laravel sees them as active
            $canceledAt = null;
            $actualEndsAt = null;
            
            if ($status === 'cancelled') {
                $canceledAt = Carbon::parse($wooSub->post_modified);
                $actualEndsAt = $endsAt; // Keep the end date if set
            } elseif ($status === 'expired') {
                $actualEndsAt = $endsAt ?? Carbon::parse($wooSub->post_modified);
            }
            // For active subscriptions, both stay null

            if ($isDryRun) {
                $this->line("  📋 Would import: Subscription #{$wooSub->subscription_id} for {$user->email}");
                $this->line("     Status: {$status}, Next billing: " . ($nextBillingAt ? $nextBillingAt->format('Y-m-d') : 'N/A'));
                return 'imported';
            }

            // Create vegbox subscription
            $vegboxSub = VegboxSubscription::create([
                'subscriber_type' => 'App\\Models\\User',  // Polymorphic relation
                'subscriber_id' => $user->id,              // Polymorphic relation
                'plan_id' => $defaultPlan->id,
                'slug' => 'woo-' . $wooSub->subscription_id,
                'name' => "WooCommerce Subscription #{$wooSub->subscription_id}",
                'description' => "Imported from WooCommerce - Payments managed by WooCommerce (Stripe/Prepaid)",
                'price' => $wooSub->order_total ?? 0.00,
                'currency' => 'GBP',
                'trial_ends_at' => $trialEndsAt,
                'starts_at' => $startsAt,
                'ends_at' => $actualEndsAt,  // Only set if actually ended/cancelled
                'canceled_at' => $canceledAt,  // Only set if cancelled
                'next_billing_at' => $skipRenewals ? null : $nextBillingAt, // Skip renewals if requested
                'woo_subscription_id' => $wooSub->subscription_id,
                'imported_from_woo' => true,
                'skip_auto_renewal' => $skipRenewals, // Flag to prevent auto-renewal
            ]);

            $renewalStatus = $skipRenewals ? ' (renewals DISABLED)' : ' (renewals ENABLED)';
            $this->info("  ✅ Imported: Subscription #{$wooSub->subscription_id} → Vegbox #{$vegboxSub->id} for {$user->email}{$renewalStatus}");
            
            return 'imported';

        } catch (\Exception $e) {
            $this->error("  ❌ Error importing subscription #{$wooSub->subscription_id}: " . $e->getMessage());
            return 'error';
        }
    }

    /**
     * Find Laravel user by WooCommerce customer ID
     */
    protected function findUserByWooCustomerId($wooCustomerId)
    {
        if (!$wooCustomerId || $wooCustomerId == 0) {
            return null;
        }

        // First try to find by woo_customer_id link
        $user = User::where('woo_customer_id', $wooCustomerId)->first();
        
        if ($user) {
            return $user;
        }

        // Fallback: Look up WooCommerce customer email and match Laravel user by email
        $wooUsers = DB::connection('wordpress')
            ->select('SELECT ID, user_email FROM D6sPMX_users WHERE ID = ?', [$wooCustomerId]);

        if (empty($wooUsers)) {
            return null;
        }
        
        $wooUser = $wooUsers[0];

        // Find Laravel user by matching email
        $user = User::where('email', $wooUser->user_email)->first();
        
        if ($user) {
            // Update the user record to link woo_customer_id for future imports
            $user->update(['woo_customer_id' => $wooCustomerId]);
        }
        
        return $user;
    }

    /**
     * Map WooCommerce status to our status
     */
    protected function mapWooStatus($wooStatus)
    {
        $statusMap = [
            'wc-active' => 'active',
            'wc-pending' => 'pending',
            'wc-on-hold' => 'on-hold',
            'wc-cancelled' => 'cancelled',
            'wc-expired' => 'expired',
            'wc-pending-cancel' => 'pending-cancel',
        ];

        return $statusMap[$wooStatus] ?? 'active';
    }
}
