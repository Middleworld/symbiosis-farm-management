<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\VegboxSubscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CompleteSubscriptionMigration extends Command
{
    protected $signature = 'subscriptions:complete-migration {--dry-run : Run without making changes} {--subscription= : Migrate specific subscription ID only}';
    
    protected $description = 'Complete the WordPress to Laravel subscription migration by creating Laravel users and migrating payment methods';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $specificSubscription = $this->option('subscription');
        
        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
        }

        $this->info('Starting complete subscription migration...');
        $this->newLine();

        // Get WordPress subscriptions that need migration
        $query = VegboxSubscription::whereNotNull('wordpress_user_id')
            ->whereNull('subscriber_id');
            
        if ($specificSubscription) {
            $query->where('id', $specificSubscription);
        }
        
        $subscriptions = $query->get();

        if ($subscriptions->isEmpty()) {
            $this->info('✅ No subscriptions need migration!');
            return 0;
        }

        $this->info("Found {$subscriptions->count()} subscriptions to migrate");
        $this->newLine();

        $migrated = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($subscriptions as $subscription) {
            $this->info("Processing Subscription #{$subscription->id}...");
            
            try {
                // Get WordPress user data
                $wpUser = $this->getWordPressUser($subscription->wordpress_user_id);
                
                if (!$wpUser) {
                    $this->error("  ❌ WordPress user #{$subscription->wordpress_user_id} not found");
                    $failed++;
                    continue;
                }

                $this->line("  WordPress User: {$wpUser->display_name} ({$wpUser->user_email})");

                // Check if Laravel user already exists
                $laravelUser = User::where('email', $wpUser->user_email)->first();
                
                if ($laravelUser) {
                    $this->line("  ✓ Laravel user already exists (ID: {$laravelUser->id})");
                } else {
                    // Create new Laravel user
                    if (!$dryRun) {
                        $laravelUser = $this->createLaravelUser($wpUser);
                        $this->info("  ✓ Created Laravel user (ID: {$laravelUser->id})");
                    } else {
                        $this->line("  [DRY RUN] Would create Laravel user");
                        $skipped++;
                        continue;
                    }
                }

                // Migrate Stripe customer ID if exists
                $stripeCustomerId = $this->getStripeCustomerId($subscription->wordpress_user_id);
                
                if ($stripeCustomerId && $laravelUser) {
                    if (!$dryRun) {
                        if (!$laravelUser->stripe_id) {
                            $laravelUser->update(['stripe_id' => $stripeCustomerId]);
                            $this->info("  ✓ Migrated Stripe customer ID: {$stripeCustomerId}");
                        } else {
                            $this->line("  ✓ Stripe ID already set");
                        }
                    } else {
                        $this->line("  [DRY RUN] Would migrate Stripe ID: {$stripeCustomerId}");
                    }
                }

                // Link subscription to Laravel user
                if (!$dryRun) {
                    $subscription->update([
                        'subscriber_id' => $laravelUser->id,
                        'subscriber_type' => User::class,
                        'skip_auto_renewal' => false, // Enable auto-renewal
                    ]);
                    $this->info("  ✓ Linked subscription to Laravel user");
                    $this->info("  ✓ Enabled auto-renewal");
                } else {
                    $this->line("  [DRY RUN] Would link to Laravel user and enable auto-renewal");
                }

                $migrated++;
                $this->newLine();

            } catch (\Exception $e) {
                $this->error("  ❌ Error: " . $e->getMessage());
                Log::error('Subscription migration failed', [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $failed++;
                $this->newLine();
            }
        }

        // Summary
        $this->newLine();
        $this->info('═══════════════════════════════════════');
        $this->info('Migration Summary:');
        $this->info('═══════════════════════════════════════');
        $this->info("✅ Migrated: {$migrated}");
        if ($failed > 0) {
            $this->error("❌ Failed: {$failed}");
        }
        if ($skipped > 0) {
            $this->warn("⏭️  Skipped (dry run): {$skipped}");
        }
        $this->newLine();

        if ($dryRun) {
            $this->warn('This was a DRY RUN. Run without --dry-run to apply changes.');
        }

        return 0;
    }

    protected function getWordPressUser($wordpressUserId)
    {
        return DB::connection('wordpress')
            ->table('users')
            ->where('ID', $wordpressUserId)
            ->first();
    }

    protected function createLaravelUser($wpUser)
    {
        // Get additional WordPress user meta
        $firstName = DB::connection('wordpress')
            ->table('usermeta')
            ->where('user_id', $wpUser->ID)
            ->where('meta_key', 'billing_first_name')
            ->value('meta_value');

        $lastName = DB::connection('wordpress')
            ->table('usermeta')
            ->where('user_id', $wpUser->ID)
            ->where('meta_key', 'billing_last_name')
            ->value('meta_value');

        return User::create([
            'name' => trim(($firstName ?? '') . ' ' . ($lastName ?? '')) ?: $wpUser->display_name,
            'email' => $wpUser->user_email,
            'password' => Hash::make(Str::random(32)), // Random password - they'll reset it
            'email_verified_at' => now(), // Mark as verified since they have WordPress account
            'wordpress_user_id' => $wpUser->ID,
        ]);
    }

    protected function getStripeCustomerId($wordpressUserId)
    {
        // Check for WooCommerce Stripe customer ID in user meta
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
