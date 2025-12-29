<?php

namespace App\Console\Commands;

use App\Models\VegboxSubscription;
use Illuminate\Console\Command;

class TestGracePeriod extends Command
{
    protected $signature = 'vegbox:test-grace-period {subscription-id}';
    protected $description = 'Test grace period and retry logic for a subscription';

    public function handle()
    {
        $subscriptionId = $this->argument('subscription-id');
        $subscription = VegboxSubscription::find($subscriptionId);

        if (!$subscription) {
            $this->error("Subscription #{$subscriptionId} not found");
            return Command::FAILURE;
        }

        $this->info("Subscription #{$subscription->id} - Grace Period Test");
        $this->newLine();

        // Display current status
        $this->line("Current Status:");
        $this->line("  Failed Payment Count: {$subscription->failed_payment_count}");
        $this->line("  Last Payment Attempt: " . ($subscription->last_payment_attempt_at ? $subscription->last_payment_attempt_at->format('Y-m-d H:i:s') : 'Never'));
        $this->line("  Next Retry At: " . ($subscription->next_retry_at ? $subscription->next_retry_at->format('Y-m-d H:i:s') : 'Not scheduled'));
        $this->line("  Grace Period Ends: " . ($subscription->grace_period_ends_at ? $subscription->grace_period_ends_at->format('Y-m-d H:i:s') : 'Not set'));
        $this->line("  Last Error: " . ($subscription->last_payment_error ?? 'None'));
        $this->newLine();

        // Check status
        $this->line("Status Checks:");
        $this->line("  In Grace Period: " . ($subscription->isInGracePeriod() ? 'Yes' : 'No'));
        $this->line("  Ready for Retry: " . ($subscription->isReadyForRetry() ? 'Yes' : 'No'));
        $this->line("  Max Retries Exceeded: " . ($subscription->hasExceededMaxRetries() ? 'Yes' : 'No'));
        $this->newLine();

        // Configuration
        $this->line("Configuration:");
        $this->line("  Grace Period Days: " . config('subscription.grace_period_days'));
        $this->line("  Max Retry Attempts: " . config('subscription.max_retry_attempts'));
        $this->line("  Retry Delays: " . implode(', ', config('subscription.retry_delays')) . ' days');
        $this->newLine();

        // Simulate failed payment (optional)
        if ($this->confirm('Simulate a failed payment?', false)) {
            $testError = 'Test: Insufficient funds for renewal';
            $subscription->recordFailedPayment($testError);
            $this->info("Failed payment recorded!");
            $this->line("  Failed Count: {$subscription->failed_payment_count}");
            $this->line("  Next Retry: " . $subscription->next_retry_at->format('Y-m-d H:i:s'));
            $this->line("  Grace Period Ends: " . $subscription->grace_period_ends_at->format('Y-m-d H:i:s'));
        }

        // Reset tracking (optional)
        if ($this->confirm('Reset retry tracking?', false)) {
            $subscription->resetRetryTracking();
            $this->info("Retry tracking reset!");
        }

        return Command::SUCCESS;
    }
}
