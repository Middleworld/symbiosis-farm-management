<?php

namespace App\Console\Commands;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ImportWooCustomers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vegbox:import-woo-customers 
                            {--dry-run : Show what would be imported without actually importing}
                            {--with-subscriptions : Only import customers who have subscriptions}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'SAFELY import WooCommerce customers into Laravel users (READ-ONLY from WooCommerce)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('╔════════════════════════════════════════════════════════════════════╗');
        $this->info('║         SAFE WooCommerce Customer Import (Read-Only)              ║');
        $this->info('╚════════════════════════════════════════════════════════════════════╝');
        $this->newLine();

        $isDryRun = $this->option('dry-run');
        $onlyWithSubscriptions = $this->option('with-subscriptions');

        if ($isDryRun) {
            $this->warn('🔍 DRY RUN MODE - No users will be created');
        }

        if ($onlyWithSubscriptions) {
            $this->info('📦 Only importing customers with active subscriptions');
        }

        $this->newLine();

        // Safety confirmation
        if (!$isDryRun && !$this->confirm('This will CREATE Laravel users from WooCommerce customers. Continue?', true)) {
            $this->error('Import cancelled.');
            return 1;
        }

        $this->newLine();
        $this->info('📊 Analyzing WooCommerce customers...');
        $this->newLine();

        // Get WooCommerce customers
        $wooCustomers = $this->getWooCommerceCustomers($onlyWithSubscriptions);

        if ($wooCustomers->isEmpty()) {
            $this->warn('No WooCommerce customers found.');
            return 0;
        }

        $this->info("Found {$wooCustomers->count()} WooCommerce customers");
        $this->newLine();

        $imported = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($wooCustomers as $wooCustomer) {
            $result = $this->importCustomer($wooCustomer, $isDryRun);
            
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
            $this->info('✅ Import complete! WooCommerce customers were NOT modified.');
            $this->warn('⚠️  Imported users have random passwords. They will need password reset.');
        }

        return 0;
    }

    /**
     * Get WooCommerce customers
     */
    protected function getWooCommerceCustomers($onlyWithSubscriptions)
    {
        if ($onlyWithSubscriptions) {
            // Only customers with subscriptions
            $customers = DB::connection('wordpress')
                ->select('
                    SELECT DISTINCT 
                        u.ID,
                        u.user_login,
                        u.user_email,
                        u.display_name,
                        u.user_registered,
                        um_first.meta_value as first_name,
                        um_last.meta_value as last_name,
                        um_billing_email.meta_value as billing_email,
                        um_billing_phone.meta_value as billing_phone
                    FROM D6sPMX_users u
                    INNER JOIN D6sPMX_postmeta pm ON u.ID = pm.meta_value
                    INNER JOIN D6sPMX_posts p ON pm.post_id = p.ID
                    LEFT JOIN D6sPMX_usermeta um_first ON u.ID = um_first.user_id AND um_first.meta_key = "first_name"
                    LEFT JOIN D6sPMX_usermeta um_last ON u.ID = um_last.user_id AND um_last.meta_key = "last_name"
                    LEFT JOIN D6sPMX_usermeta um_billing_email ON u.ID = um_billing_email.user_id AND um_billing_email.meta_key = "billing_email"
                    LEFT JOIN D6sPMX_usermeta um_billing_phone ON u.ID = um_billing_phone.user_id AND um_billing_phone.meta_key = "billing_phone"
                    WHERE pm.meta_key = "_customer_user"
                    AND p.post_type = "shop_subscription"
                    AND p.post_status IN ("wc-active", "wc-pending", "wc-on-hold", "wc-cancelled")
                    ORDER BY u.ID
                ');
        } else {
            // All WooCommerce customers
            $customers = DB::connection('wordpress')
                ->select('
                    SELECT 
                        u.ID,
                        u.user_login,
                        u.user_email,
                        u.display_name,
                        u.user_registered,
                        um_first.meta_value as first_name,
                        um_last.meta_value as last_name,
                        um_billing_email.meta_value as billing_email,
                        um_billing_phone.meta_value as billing_phone
                    FROM D6sPMX_users u
                    LEFT JOIN D6sPMX_usermeta um_first ON u.ID = um_first.user_id AND um_first.meta_key = "first_name"
                    LEFT JOIN D6sPMX_usermeta um_last ON u.ID = um_last.user_id AND um_last.meta_key = "last_name"
                    LEFT JOIN D6sPMX_usermeta um_billing_email ON u.ID = um_billing_email.user_id AND um_billing_email.meta_key = "billing_email"
                    LEFT JOIN D6sPMX_usermeta um_billing_phone ON u.ID = um_billing_phone.user_id AND um_billing_phone.meta_key = "billing_phone"
                    WHERE u.ID > 1
                    ORDER BY u.ID
                ');
        }

        return collect($customers);
    }

    /**
     * Import a single customer
     */
    protected function importCustomer($wooCustomer, $isDryRun)
    {
        try {
            // Check if user already exists by email
            if (!$isDryRun) {
                $existingUser = User::where('email', $wooCustomer->user_email)->first();
                
                if ($existingUser) {
                    // Update woo_customer_id if not set
                    if (!$existingUser->woo_customer_id) {
                        $existingUser->update(['woo_customer_id' => $wooCustomer->ID]);
                        $this->line("  🔗 Updated: {$wooCustomer->user_email} (linked WooID: {$wooCustomer->ID})");
                    } else {
                        $this->line("  ⏭️  Exists: {$wooCustomer->user_email} (Laravel #{$existingUser->id})");
                    }
                    return 'skipped';
                }
            }

            // Prepare user data
            $name = trim($wooCustomer->first_name . ' ' . $wooCustomer->last_name);
            if (empty($name)) {
                $name = $wooCustomer->display_name ?: $wooCustomer->user_login;
            }

            $email = $wooCustomer->billing_email ?: $wooCustomer->user_email;

            if ($isDryRun) {
                $this->line("  📋 Would import: WooID {$wooCustomer->ID} - {$name} ({$email})");
                return 'imported';
            }

            // Create Laravel user
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make(Str::random(32)), // Random password - user will reset
                'email_verified_at' => now(), // Auto-verify since they're existing WooCommerce customers
                'woo_customer_id' => $wooCustomer->ID,
                'created_at' => Carbon::parse($wooCustomer->user_registered),
            ]);

            $this->info("  ✅ Imported: WooID {$wooCustomer->ID} → Laravel #{$user->id} - {$name} ({$email})");
            
            return 'imported';

        } catch (\Exception $e) {
            $this->error("  ❌ Error importing WooID {$wooCustomer->ID}: " . $e->getMessage());
            return 'error';
        }
    }
}
