<?php

namespace App\Console\Commands;

use App\Models\CsaSubscription;
use App\Models\WordPressUser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncWordPressUsers extends Command
{
    protected $signature = 'subscriptions:sync-wp-users {--dry-run : Show what would be created without actually creating}';
    protected $description = 'Create WordPress users for imported subscriptions that are missing them';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        // Get all subscriptions without WordPress users
        $subscriptions = CsaSubscription::where('imported_from_woo', true)->get();
        
        $missing = 0;
        $created = 0;
        $skipped = 0;

        foreach ($subscriptions as $subscription) {
            // Check if WordPress user exists
            $wpUser = WordPressUser::where('user_email', $subscription->customer_email)->first();
            
            if ($wpUser) {
                $skipped++;
                continue;
            }

            $missing++;
            
            // Generate username from email
            $username = explode('@', $subscription->customer_email)[0];
            $username = Str::slug($username, '_');
            
            // Check if username exists, make unique if needed
            $originalUsername = $username;
            $counter = 1;
            while (WordPressUser::where('user_login', $username)->exists()) {
                $username = $originalUsername . $counter;
                $counter++;
            }

            if ($dryRun) {
                $this->line("Would create WP user: {$username} ({$subscription->customer_email})");
            } else {
                try {
                    // Create WordPress user
                    DB::connection('wordpress')->table('users')->insert([
                        'user_login' => $username,
                        'user_pass' => password_hash(Str::random(32), PASSWORD_BCRYPT),
                        'user_nicename' => $username,
                        'user_email' => $subscription->customer_email,
                        'user_url' => '',
                        'user_registered' => now(),
                        'user_activation_key' => '',
                        'user_status' => 0,
                        'display_name' => $subscription->customer_name ?? $username,
                    ]);
                    
                    $userId = DB::connection('wordpress')->getPdo()->lastInsertId();
                    
                    // Add customer role
                    DB::connection('wordpress')->table('usermeta')->insert([
                        'user_id' => $userId,
                        'meta_key' => 'wp_capabilities',
                        'meta_value' => serialize(['customer' => true]),
                    ]);
                    
                    DB::connection('wordpress')->table('usermeta')->insert([
                        'user_id' => $userId,
                        'meta_key' => 'wp_user_level',
                        'meta_value' => '0',
                    ]);
                    
                    $this->info("✅ Created WP user: {$username} (ID: {$userId}) - {$subscription->customer_email}");
                    $created++;
                    
                } catch (\Exception $e) {
                    $this->error("❌ Failed to create user for {$subscription->customer_email}: " . $e->getMessage());
                }
            }
        }

        $this->newLine();
        $this->info('=== SUMMARY ===');
        $this->line("Subscriptions checked: {$subscriptions->count()}");
        $this->line("Already had WP users: {$skipped}");
        $this->line("Missing WP users: {$missing}");
        
        if ($dryRun) {
            $this->warn("Would create: {$missing} WordPress users");
            $this->newLine();
            $this->comment('Run without --dry-run to actually create the users:');
            $this->comment('  php artisan subscriptions:sync-wp-users');
        } else {
            $this->info("Created: {$created} WordPress users");
        }

        return 0;
    }
}
