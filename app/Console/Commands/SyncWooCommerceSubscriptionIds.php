<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncWooCommerceSubscriptionIds extends Command
{
    protected $signature = 'vegbox:sync-woo-ids';
    protected $description = 'Sync WooCommerce subscription IDs to Laravel vegbox_subscriptions table';

    public function handle()
    {
        $this->info('Syncing WooCommerce subscription IDs to vegbox_subscriptions...');

        // Get all vegbox subscriptions that don't have a WooCommerce ID yet
        $subscriptions = DB::connection('mysql')
            ->table('vegbox_subscriptions')
            ->whereNull('woocommerce_subscription_id')
            ->get();

        $this->info("Found {$subscriptions->count()} subscriptions to sync");

        $synced = 0;
        $failed = 0;

        foreach ($subscriptions as $subscription) {
            try {
                // Get WordPress user ID from vegbox subscription
                $wordpressUserId = $subscription->wordpress_user_id;
                
                if (!$wordpressUserId) {
                    $this->warn("Subscription #{$subscription->id} has no wordpress_user_id, skipping");
                    $failed++;
                    continue;
                }

                // Find matching WooCommerce subscription by customer_id
                // Note: Laravel automatically applies the prefix from config, so just use table names directly
                $wooSubscription = DB::connection('wordpress')
                    ->table('posts as p')
                    ->join('postmeta as pm', 'p.ID', '=', 'pm.post_id')
                    ->where('p.post_type', 'shop_subscription')
                    ->where('pm.meta_key', '_customer_user')
                    ->where('pm.meta_value', $wordpressUserId)
                    ->where('p.post_status', '!=', 'trash')
                    ->select('p.ID')
                    ->orderBy('p.ID', 'desc')
                    ->first();

                if ($wooSubscription) {
                    // Update Laravel subscription with WooCommerce ID
                    DB::connection('mysql')
                        ->table('vegbox_subscriptions')
                        ->where('id', $subscription->id)
                        ->update(['woocommerce_subscription_id' => $wooSubscription->ID]);

                    $this->info("✓ Synced Laravel #{$subscription->id} → WooCommerce #{$wooSubscription->ID}");
                    $synced++;
                } else {
                    $this->warn("✗ No WooCommerce subscription found for Laravel #{$subscription->id} (WordPress User: {$wordpressUserId})");
                    $failed++;
                }

            } catch (\Exception $e) {
                $this->error("Error syncing subscription #{$subscription->id}: " . $e->getMessage());
                $failed++;
            }
        }

        $this->newLine();
        $this->info("Sync complete!");
        $this->info("✓ Synced: {$synced}");
        if ($failed > 0) {
            $this->warn("✗ Failed: {$failed}");
        }

        return 0;
    }
}
