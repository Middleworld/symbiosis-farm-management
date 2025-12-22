<?php

namespace App\Console\Commands;

use App\Models\VegboxSubscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MigrateStripeCustomerIds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:migrate-stripe-ids {--dry-run : Run without making changes} {--subscription= : Migrate specific subscription ID only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate Stripe customer IDs from WooCommerce to Laravel subscriptions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $specificSubscription = $this->option('subscription');

        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
        }

        $this->info('Starting Stripe customer ID migration...');
        $this->newLine();

        // Get subscriptions that need Stripe customer ID migration
        $query = VegboxSubscription::whereNotNull('wordpress_user_id')
            ->whereNotNull('subscriber_id')
            ->whereNull('stripe_customer_id');

        if ($specificSubscription) {
            $query->where('id', $specificSubscription);
        }

        $subscriptions = $query->get();

        if ($subscriptions->isEmpty()) {
            $this->info('✅ No subscriptions need Stripe customer ID migration!');
            return 0;
        }

        $this->info("Found {$subscriptions->count()} subscriptions needing Stripe customer ID migration");
        $this->newLine();

        $migrated = 0;
        $failed = 0;

        foreach ($subscriptions as $subscription) {
            $this->info("Processing Subscription #{$subscription->id}...");

            try {
                // Get Stripe customer ID from WordPress user meta
                $stripeCustomerId = $this->getStripeCustomerId($subscription->wordpress_user_id);

                if (!$stripeCustomerId) {
                    $this->warn("  ⚠️  No Stripe customer ID found for WordPress user #{$subscription->wordpress_user_id}");
                    $failed++;
                    continue;
                }

                $this->line("  Found Stripe customer ID: {$stripeCustomerId}");

                if (!$dryRun) {
                    $subscription->update(['stripe_customer_id' => $stripeCustomerId]);
                    $this->info("  ✓ Migrated Stripe customer ID to subscription #{$subscription->id}");
                } else {
                    $this->line("  [DRY RUN] Would migrate Stripe customer ID: {$stripeCustomerId}");
                }

                $migrated++;

            } catch (\Exception $e) {
                $this->error("  ❌ Error: " . $e->getMessage());
                Log::error('Stripe customer ID migration failed', [
                    'subscription_id' => $subscription->id,
                    'wordpress_user_id' => $subscription->wordpress_user_id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $failed++;
            }
            $this->newLine();
        }

        // Summary
        $this->newLine();
        $this->info('═══════════════════════════════════════');
        $this->info('Stripe Customer ID Migration Summary:');
        $this->info('═══════════════════════════════════════');
        $this->info("✅ Migrated: {$migrated}");
        if ($failed > 0) {
            $this->error("❌ Failed: {$failed}");
        }
        $this->newLine();

        if ($dryRun) {
            $this->warn('This was a DRY RUN. Run without --dry-run to apply changes.');
        }

        return 0;
    }

    protected function getStripeCustomerId($wordpressUserId)
    {
        // First try to find Stripe customer ID in subscription meta
        // We need to get the subscription ID from the vegbox subscription
        $vegboxSub = VegboxSubscription::where('wordpress_user_id', $wordpressUserId)->first();
        
        if ($vegboxSub && $vegboxSub->woo_subscription_id) {
            $stripeCustomerId = DB::connection('wordpress')
                ->table('postmeta')
                ->where('post_id', $vegboxSub->woo_subscription_id)
                ->where('meta_key', '_stripe_customer_id')
                ->value('meta_value');
                
            if ($stripeCustomerId) {
                return $stripeCustomerId;
            }
        }

        // Fallback: Check for WooCommerce Stripe customer ID in user meta
        $stripeCustomerId = DB::connection('wordpress')
            ->table('usermeta')
            ->where('user_id', $wordpressUserId)
            ->where('meta_key', '_stripe_customer_id')
            ->value('meta_value');

        if ($stripeCustomerId) {
            return $stripeCustomerId;
        }

        // Alternative meta key used by some Stripe plugins
        return DB::connection('wordpress')
            ->table('usermeta')
            ->where('user_id', $wordpressUserId)
            ->where('meta_key', 'stripe_customer_id')
            ->value('meta_value');
    }
}
