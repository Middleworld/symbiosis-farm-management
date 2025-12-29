<?php

namespace App\Console\Commands;

use App\Models\VegboxSubscription;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Carbon\Carbon;

class MonitorSubscriptionHealth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vegbox:monitor-health 
                            {--notify : Send notifications for critical issues}
                            {--fix : Attempt to fix detected issues automatically}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor subscription system health and detect issues';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $shouldNotify = $this->option('notify');
        $shouldFix = $this->option('fix');

        $this->info('🔍 Subscription Health Check');
        $this->newLine();

        $issues = [];

        // Check 1: Overdue subscriptions (next_billing_at is past and not processed)
        $overdueCount = VegboxSubscription::query()
            ->active()
            ->where('skip_auto_renewal', false)
            ->whereNotNull('next_billing_at')
            ->where('next_billing_at', '<', now()->subHours(2))
            ->count();

        if ($overdueCount > 0) {
            $this->warn("⚠️  {$overdueCount} subscriptions are overdue (>2 hours past billing date)");
            $issues[] = "Overdue subscriptions: {$overdueCount}";
            
            if ($shouldFix) {
                $this->info("  → Running renewal processor...");
                $this->call('vegbox:process-renewals', ['--days-ahead' => 0]);
            }
        } else {
            $this->info("✓ No overdue subscriptions");
        }

        // Check 2: Subscriptions with many failed payment attempts
        $criticalFailures = VegboxSubscription::query()
            ->active()
            ->where('failed_payment_count', '>=', 3)
            ->whereNull('grace_period_ends_at')
            ->count();

        if ($criticalFailures > 0) {
            $this->warn("⚠️  {$criticalFailures} subscriptions have ≥3 failed payments without grace period set");
            $issues[] = "Critical payment failures: {$criticalFailures}";
            
            if ($shouldFix) {
                $this->info("  → Setting grace periods...");
                VegboxSubscription::query()
                    ->active()
                    ->where('failed_payment_count', '>=', 3)
                    ->whereNull('grace_period_ends_at')
                    ->update(['grace_period_ends_at' => now()->addDays(7)]);
            }
        } else {
            $this->info("✓ No critical payment failures");
        }

        // Check 3: Expired grace periods (should be cancelled)
        $expiredGrace = VegboxSubscription::query()
            ->active()
            ->whereNotNull('grace_period_ends_at')
            ->where('grace_period_ends_at', '<', now())
            ->count();

        if ($expiredGrace > 0) {
            $this->warn("⚠️  {$expiredGrace} subscriptions have expired grace periods (should be cancelled)");
            $issues[] = "Expired grace periods: {$expiredGrace}";
            
            if ($shouldFix) {
                $this->info("  → Cancelling subscriptions with expired grace...");
                $subs = VegboxSubscription::query()
                    ->active()
                    ->whereNotNull('grace_period_ends_at')
                    ->where('grace_period_ends_at', '<', now())
                    ->get();
                
                foreach ($subs as $sub) {
                    $sub->update([
                        'canceled_at' => now(),
                        'ends_at' => now(),
                    ]);
                    
                    Log::warning('Subscription auto-cancelled due to expired grace period', [
                        'subscription_id' => $sub->id,
                        'grace_period_ended' => $sub->grace_period_ends_at,
                    ]);
                }
            }
        } else {
            $this->info("✓ No expired grace periods");
        }

        // Check 4: Subscriptions without next_billing_at (data integrity issue)
        $missingBillingDate = VegboxSubscription::query()
            ->active()
            ->where('skip_auto_renewal', false)
            ->whereNull('next_billing_at')
            ->count();

        if ($missingBillingDate > 0) {
            $this->error("❌ {$missingBillingDate} active subscriptions missing next_billing_at");
            $issues[] = "Missing billing dates: {$missingBillingDate}";
            
            if ($shouldFix) {
                $this->info("  → Setting default billing dates (1 month from now)...");
                VegboxSubscription::query()
                    ->active()
                    ->where('skip_auto_renewal', false)
                    ->whereNull('next_billing_at')
                    ->update(['next_billing_at' => now()->addMonth()]);
            }
        } else {
            $this->info("✓ All active subscriptions have billing dates");
        }

        // Check 5: Subscriptions with conflicting status (cancelled but marked active)
        $conflictingStatus = VegboxSubscription::query()
            ->whereNotNull('canceled_at')
            ->where('skip_auto_renewal', false)
            ->count();

        if ($conflictingStatus > 0) {
            $this->warn("⚠️  {$conflictingStatus} cancelled subscriptions have skip_auto_renewal=false");
            $issues[] = "Conflicting status: {$conflictingStatus}";
            
            if ($shouldFix) {
                $this->info("  → Fixing status conflicts...");
                VegboxSubscription::query()
                    ->whereNotNull('canceled_at')
                    ->where('skip_auto_renewal', false)
                    ->update(['skip_auto_renewal' => true]);
            }
        } else {
            $this->info("✓ No conflicting statuses");
        }

        // Check 6: Subscriptions ready for retry but not scheduled
        $needsRetry = VegboxSubscription::query()
            ->active()
            ->where('failed_payment_count', '>', 0)
            ->where('failed_payment_count', '<', 3)
            ->whereNull('next_retry_at')
            ->count();

        if ($needsRetry > 0) {
            $this->warn("⚠️  {$needsRetry} subscriptions with failed payments missing retry schedule");
            $issues[] = "Missing retry schedule: {$needsRetry}";
            
            if ($shouldFix) {
                $this->info("  → Running retry scheduler...");
                $this->call('vegbox:retry-failed-payments', ['--dry-run' => false]);
            }
        } else {
            $this->info("✓ All failed payments have retry schedules");
        }

        $this->newLine();
        $this->info('=== SUMMARY ===');
        
        if (empty($issues)) {
            $this->info('🎉 All checks passed! Subscription system is healthy.');
        } else {
            $this->warn('Issues found: ' . count($issues));
            foreach ($issues as $issue) {
                $this->line("  • {$issue}");
            }
            
            if ($shouldFix) {
                $this->info("\n✓ Automatic fixes applied");
            } else {
                $this->comment("\nRun with --fix to automatically resolve issues");
            }
            
            if ($shouldNotify) {
                $this->sendHealthAlert($issues);
            }
        }

        return count($issues) > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Send health alert notification to admin
     */
    private function sendHealthAlert(array $issues): void
    {
        $adminEmail = env('ADMIN_EMAIL', 'middleworldfarms@gmail.com');
        
        Log::warning('Subscription health check found issues', [
            'issues' => $issues,
            'timestamp' => now(),
        ]);
        
        $this->info("Health alert logged (email notifications TODO)");
    }
}
