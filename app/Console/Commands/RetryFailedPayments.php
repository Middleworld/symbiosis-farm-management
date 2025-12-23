<?php

namespace App\Console\Commands;

use App\Models\VegboxSubscription;
use App\Services\VegboxPaymentService;
use App\Notifications\PaymentRetryReminder;
use App\Notifications\FinalPaymentWarning;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class RetryFailedPayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vegbox:retry-failed-payments 
                            {--max-retries=3 : Maximum retry attempts before giving up}
                            {--dry-run : Show what would be retried without processing}
                            {--subscription-id= : Retry specific subscription ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retry failed subscription payments with exponential backoff';

    protected VegboxPaymentService $paymentService;

    public function __construct(VegboxPaymentService $paymentService)
    {
        parent::__construct();
        $this->paymentService = $paymentService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $maxRetries = (int) $this->option('max-retries');
        $isDryRun = $this->option('dry-run');
        $specificSubscriptionId = $this->option('subscription-id');

        $this->info('Retrying failed subscription payments' . ($isDryRun ? ' (DRY RUN)' : ''));
        $this->info("Max retries: {$maxRetries}");
        $this->newLine();

        // Get subscriptions with failed payments ready for retry
        // Exponential backoff: 1st retry after 1 hour, 2nd after 4 hours, 3rd after 12 hours
        $query = VegboxSubscription::query()
            ->active()
            ->where('failed_payment_count', '>', 0)
            ->where('failed_payment_count', '<=', $maxRetries)
            ->where(function ($q) {
                $q->whereNull('next_retry_at')
                  ->orWhere('next_retry_at', '<=', now());
            })
            ->orderBy('failed_payment_count')
            ->orderBy('last_payment_attempt_at');

        if ($specificSubscriptionId) {
            $query->where('id', $specificSubscriptionId);
        }

        $subscriptions = $query->with(['subscriber', 'plan'])->get();

        if ($subscriptions->isEmpty()) {
            $this->info('No subscriptions ready for retry.');
            return Command::SUCCESS;
        }

        $this->info("Found {$subscriptions->count()} subscriptions ready for retry");
        $this->newLine();

        $successful = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($subscriptions as $subscription) {
            $this->line("Processing subscription #{$subscription->id} (retry #{$subscription->failed_payment_count})...");

            if ($isDryRun) {
                $this->info("  [DRY RUN] Would retry payment for subscription #{$subscription->id}");
                continue;
            }

            try {
                $result = $this->paymentService->processSubscriptionRenewal($subscription);

                if ($result['success']) {
                    $successful++;
                    $this->info("  ✓ Payment successful: {$result['transaction_id']}");
                    
                    // Reset failure tracking
                    $subscription->update([
                        'failed_payment_count' => 0,
                        'last_payment_error' => null,
                        'next_retry_at' => null,
                        'grace_period_ends_at' => null,
                    ]);
                } else {
                    $failed++;
                    $this->error("  ✗ Payment failed: {$result['error']}");
                    
                    // Increment failure count and schedule next retry with exponential backoff
                    $retryCount = $subscription->failed_payment_count + 1;
                    $nextRetry = $this->calculateNextRetry($retryCount);
                    
                    $subscription->update([
                        'failed_payment_count' => $retryCount,
                        'last_payment_error' => $result['error'],
                        'last_payment_attempt_at' => now(),
                        'next_retry_at' => $nextRetry,
                    ]);
                    
                    // Send dunning emails
                    $subscriber = $subscription->subscriber;
                    if ($subscriber) {
                        if ($retryCount === 1) {
                            $subscriber->notify(new PaymentRetryReminder($subscription, $retryCount));
                            $this->info("  → Sent first reminder email");
                        } elseif ($retryCount === 2) {
                            $subscriber->notify(new PaymentRetryReminder($subscription, $retryCount));
                            $this->info("  → Sent second reminder email");
                        } elseif ($retryCount >= $maxRetries) {
                            $subscriber->notify(new FinalPaymentWarning($subscription));
                            $this->info("  → Sent final warning email");
                        }
                    }
                    
                    if ($retryCount >= $maxRetries) {
                        $this->warn("  ⚠️  Max retries reached. Setting grace period.");
                        
                        // Give 7 days grace period to update payment method
                        $subscription->update([
                            'grace_period_ends_at' => now()->addDays(7),
                        ]);
                    } else {
                        $this->info("  → Next retry scheduled: {$nextRetry->format('Y-m-d H:i:s')}");
                    }
                }
            } catch (\Exception $e) {
                $failed++;
                $this->error("  ✗ Error: {$e->getMessage()}");
                
                Log::error('Payment retry exception', [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            $this->newLine();
        }

        // Summary
        $this->info('=== SUMMARY ===');
        $this->info("Successful: {$successful}");
        $this->error("Failed: {$failed}");
        
        if ($isDryRun) {
            $this->warn('[DRY RUN] No changes were made');
        }

        return Command::SUCCESS;
    }

    /**
     * Calculate next retry time with exponential backoff
     * 1st retry: 1 hour
     * 2nd retry: 4 hours
     * 3rd retry: 12 hours
     */
    private function calculateNextRetry(int $retryCount): Carbon
    {
        $hours = match ($retryCount) {
            1 => 1,
            2 => 4,
            3 => 12,
            default => 24,
        };

        return now()->addHours($hours);
    }
}
