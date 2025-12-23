<?php

namespace App\Console\Commands;

use App\Models\VegboxSubscription;
use App\Models\User;
use App\Services\VegboxPaymentService;
use App\Services\WooCommerceOrderService;
use App\Notifications\DailyRenewalSummary;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ProcessSubscriptionRenewals extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vegbox:process-renewals {--dry-run : Show what would be processed without making changes} {--days-ahead=1 : Process renewals due within this many days} {--subscription-id= : Process specific subscription ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process vegbox subscription renewals and payments';

    protected VegboxPaymentService $paymentService;
    protected WooCommerceOrderService $orderService;

    public function __construct(VegboxPaymentService $paymentService, WooCommerceOrderService $orderService)
    {
        parent::__construct();
        $this->paymentService = $paymentService;
        $this->orderService = $orderService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $daysAhead = (int) $this->option('days-ahead');
        $specificSubscriptionId = $this->option('subscription-id');
        $processingWindowEnd = now()->addDays($daysAhead);

        $this->info("Processing vegbox subscription renewals" . ($isDryRun ? ' (DRY RUN)' : ''));

        // Process retry attempts first
        $this->processRetryAttempts($isDryRun);

        // Cancel subscriptions with expired grace periods
        $this->cancelExpiredGracePeriods($isDryRun);

        // Get subscriptions due for renewal
        // Use lockForUpdate() to prevent race conditions and double charging
        $query = VegboxSubscription::query()
            ->active()
            ->whereNotNull('next_billing_at')
            ->where('next_billing_at', '<=', $processingWindowEnd)
            ->where('skip_auto_renewal', false) // Exclude WooCommerce-managed subscriptions
            ->lockForUpdate(); // Pessimistic lock to prevent concurrent processing

        if ($specificSubscriptionId) {
            $query->where('id', $specificSubscriptionId);
        }

        $dueSubscriptions = $query->with('plan')->get();

        $this->info("Found {$dueSubscriptions->count()} subscriptions due for renewal within {$daysAhead} days");

        if ($dueSubscriptions->isEmpty()) {
            $this->info('No subscriptions to process.');
            return Command::SUCCESS;
        }

        $successful = 0;
        $failed = 0;
        $skipped = 0;
        $totalRevenue = 0;
        $failedSubscriptions = [];

        $this->newLine();
        $this->info('Processing renewals...');

        $progressBar = $this->output->createProgressBar($dueSubscriptions->count());
        $progressBar->start();

        foreach ($dueSubscriptions as $subscription) {
            $result = $this->processSubscriptionRenewal($subscription, $isDryRun, $processingWindowEnd);

            switch ($result['status']) {
                case 'success':
                    $successful++;
                    if (isset($result['amount'])) {
                        $totalRevenue += $result['amount'];
                    }
                    break;
                case 'failed':
                    $failed++;
                    $failedSubscriptions[] = [
                        'id' => $subscription->id,
                        'customer_name' => isset($subscription->subscriber->name) ? $subscription->subscriber->name : 'Unknown',
                        'reason' => isset($result['reason']) ? $result['reason'] : 'Unknown error'
                    ];
                    break;
                case 'skipped':
                    $skipped++;
                    break;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("Renewal processing complete:");
        $this->line("  ✅ Successful: {$successful}");
        $this->line("  ❌ Failed: {$failed}");
        $this->line("  ⏭️  Skipped: {$skipped}");
        $this->line("  💰 Total Revenue: £" . number_format($totalRevenue, 2));

        // Send admin notification if not dry run
        if (!$isDryRun && $dueSubscriptions->count() > 0) {
            $this->sendAdminNotification([
                'total_processed' => $dueSubscriptions->count(),
                'successful' => $successful,
                'failed' => $failed,
                'skipped' => $skipped,
                'total_revenue' => $totalRevenue,
                'failed_subscriptions' => $failedSubscriptions
            ]);
        }

        // Log summary
        Log::info('Vegbox subscription renewal processing completed', [
            'total_processed' => $dueSubscriptions->count(),
            'successful' => $successful,
            'failed' => $failed,
            'skipped' => $skipped,
            'total_revenue' => $totalRevenue,
            'dry_run' => $isDryRun
        ]);

        return Command::SUCCESS;
    }

    /**
     * Process renewal for a single subscription
     */
    private function processSubscriptionRenewal(VegboxSubscription $subscription, bool $isDryRun, Carbon $processingWindowEnd): array
    {
        try {
            // Check if subscription is actually due
            if (!$this->isSubscriptionDue($subscription, $processingWindowEnd)) {
                return [
                    'status' => 'skipped',
                    'reason' => 'Not yet due for renewal'
                ];
            }

            // Check if subscription has been paused
            if ($subscription->isPaused()) {
                Log::info('Skipping paused subscription renewal', [
                    'subscription_id' => $subscription->id,
                    'paused_until' => $subscription->pause_until
                ]);

                return [
                    'status' => 'skipped',
                    'reason' => 'Subscription is paused'
                ];
            }

            $this->logRenewalAttempt($subscription, 'starting');

            if ($isDryRun) {
                $this->logRenewalAttempt($subscription, 'dry_run_success');
                return [
                    'status' => 'success',
                    'reason' => 'Dry run - would process payment'
                ];
            }

            // Process the payment
            $paymentResult = $this->paymentService->processSubscriptionRenewal($subscription);

            if ($paymentResult['success']) {
                // Payment successful - update subscription
                $this->handleSuccessfulRenewal($subscription, $paymentResult);
                $this->logRenewalAttempt($subscription, 'success', $paymentResult);

                return [
                    'status' => 'success',
                    'transaction_id' => $paymentResult['transaction_id'],
                    'amount' => isset($paymentResult['amount']) ? $paymentResult['amount'] : $subscription->price
                ];
            } else {
                // Payment failed - handle failure
                $this->handleFailedRenewal($subscription, $paymentResult);
                $this->logRenewalAttempt($subscription, 'failed', $paymentResult);

                return [
                    'status' => 'failed',
                    'error' => isset($paymentResult['error']) ? $paymentResult['error'] : 'Unknown error',
                    'code' => isset($paymentResult['code']) ? $paymentResult['code'] : 'UNKNOWN',
                    'reason' => isset($paymentResult['error']) ? $paymentResult['error'] : 'Payment processing failed'
                ];
            }

        } catch (\Exception $e) {
            Log::error('Exception processing subscription renewal', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'status' => 'failed',
                'error' => 'Exception: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check if subscription is due for renewal
     */
    private function isSubscriptionDue(VegboxSubscription $subscription, Carbon $processingWindowEnd): bool
    {
        if (!$subscription->next_billing_at) {
            return false;
        }

        return $subscription->next_billing_at->lte($processingWindowEnd);
    }

    /**
     * Handle successful renewal
     */
    private function handleSuccessfulRenewal(VegboxSubscription $subscription, array $paymentResult): void
    {
        // Calculate next billing date
        $nextBillingAt = $this->calculateNextBillingDate($subscription);

        // Update subscription
        $subscription->update([
            'next_billing_at' => $nextBillingAt,
            'ends_at' => $nextBillingAt, // Extend the subscription
        ]);

        // Create WooCommerce renewal order
        $orderResult = $this->orderService->createRenewalOrder($subscription, $paymentResult);
        
        if ($orderResult['success']) {
            Log::info('Created renewal order', [
                'order_id' => $orderResult['order_id'],
                'subscription_id' => $subscription->id
            ]);
        } else {
            Log::warning('Failed to create renewal order', [
                'subscription_id' => $subscription->id,
                'error' => $orderResult['message']
            ]);
        }

        // Log the successful renewal
        Log::info('Subscription renewed successfully', [
            'subscription_id' => $subscription->id,
            'customer_id' => $subscription->subscriber_id,
            'amount' => $subscription->price,
            'next_billing_at' => $nextBillingAt,
            'transaction_id' => $paymentResult['transaction_id'],
            'order_id' => $orderResult['order_id'] ?? null
        ]);
    }

    /**
     * Handle failed renewal
     */
    private function handleFailedRenewal(VegboxSubscription $subscription, array $paymentResult): void
    {
        $errorCode = $paymentResult['code'] ?? 'UNKNOWN';

        // Different handling based on error type
        switch ($errorCode) {
            case 'INSUFFICIENT_FUNDS':
                // Mark for retry or suspension
                $this->handleInsufficientFunds($subscription, $paymentResult);
                break;

            case 'PAYMENT_FAILED':
            default:
                // General payment failure
                $this->handlePaymentFailure($subscription, $paymentResult);
                break;
        }

        Log::warning('Subscription renewal failed', [
            'subscription_id' => $subscription->id,
            'customer_id' => $subscription->subscriber_id,
            'error_code' => $errorCode,
            'error_message' => $paymentResult['error']
        ]);
    }

    /**
     * Handle insufficient funds
     */
    private function handleInsufficientFunds(VegboxSubscription $subscription, array $paymentResult): void
    {
        // For now, we'll mark the subscription as canceled
        // In a real implementation, you might want to:
        // - Send payment reminder emails
        // - Allow grace periods
        // - Set up retry schedules

        $subscription->update([
            'canceled_at' => now(),
            'ends_at' => now(), // End immediately
        ]);

        Log::warning('Subscription canceled due to insufficient funds', [
            'subscription_id' => $subscription->id,
            'customer_id' => $subscription->subscriber_id,
            'required_amount' => $subscription->price,
            'available_balance' => $paymentResult['available'] ?? 0
        ]);
    }

    /**
     * Handle general payment failure
     */
    private function handlePaymentFailure(VegboxSubscription $subscription, array $paymentResult): void
    {
        // For general failures, we'll also cancel for now
        // In production, you might want to retry or investigate

        $subscription->update([
            'canceled_at' => now(),
            'ends_at' => now(),
        ]);

        Log::error('Subscription canceled due to payment failure', [
            'subscription_id' => $subscription->id,
            'customer_id' => $subscription->subscriber_id,
            'error' => $paymentResult['error']
        ]);
    }

    /**
     * Calculate next billing date based on subscription plan
     * Handles Christmas closure edge cases with proper timezone handling
     */
    private function calculateNextBillingDate(VegboxSubscription $subscription): Carbon
    {
        $currentBillingDate = $subscription->next_billing_at ?? now();
        $interval = (int) ($subscription->billing_frequency ?? 1);
        
        // Calculate the normal next billing date
        $nextBillingDate = match($subscription->billing_period) {
            'week' => $currentBillingDate->copy()->addWeeks($interval),
            'month' => $currentBillingDate->copy()->addMonths($interval),
            'year' => $currentBillingDate->copy()->addYears($interval),
            default => $currentBillingDate->copy()->addMonth(),
        };
        
        // Christmas closure: Dec 21, 2025 - May 1, 2026
        // If next billing would be between Dec 21 and May 1, skip to April 10 (3 weeks before reopening)
        // Use startOfDay() to handle edge case of billing exactly on Dec 21
        $closureStart = Carbon::parse('2025-12-21 00:00:00', config('app.timezone'));
        $closureEnd = Carbon::parse('2026-05-01 23:59:59', config('app.timezone'));
        $resumeBilling = Carbon::parse('2026-04-10 09:00:00', config('app.timezone'));
        
        // Edge case: billing on exactly Dec 21 should be allowed (last delivery before closure)
        // Only pause if billing would occur AFTER Dec 21
        if ($nextBillingDate->gt($closureStart) && $nextBillingDate->lte($closureEnd)) {
            // Pause subscription and skip to April 10, 2026
            Log::info('Christmas closure: pausing subscription', [
                'subscription_id' => $subscription->id,
                'original_next_billing' => $nextBillingDate->toDateTimeString(),
                'new_next_billing' => $resumeBilling->toDateTimeString(),
                'closure_start' => $closureStart->toDateTimeString(),
                'closure_end' => $closureEnd->toDateTimeString(),
            ]);
            
            $subscription->update(['skip_auto_renewal' => true]);
            return $resumeBilling;
        }
        
        // Edge case: if we're already in the closure period and resuming, don't re-pause
        if ($nextBillingDate->gte($resumeBilling) && now()->lt($closureEnd)) {
            Log::info('Resuming billing post-closure', [
                'subscription_id' => $subscription->id,
                'next_billing' => $nextBillingDate->toDateTimeString(),
            ]);
        }
        
        return $nextBillingDate;
    }

    /**
     * Log renewal attempt
     */
    private function logRenewalAttempt(VegboxSubscription $subscription, string $status, array $details = []): void
    {
        $logData = [
            'subscription_id' => $subscription->id,
            'customer_id' => $subscription->subscriber_id,
            'plan_id' => $subscription->plan_id,
            'amount' => $subscription->price,
            'status' => $status,
            'timestamp' => now(),
        ];

        if (!empty($details)) {
            $logData['details'] = $details;
        }

        Log::info('Subscription renewal attempt', $logData);
    }

    /**
     * Send admin notification with daily summary
     */
    private function sendAdminNotification(array $summary): void
    {
        try {
            // Get admin user (you can configure this in .env or use a specific admin email)
            $adminEmail = env('ADMIN_EMAIL', 'middleworldfarms@gmail.com');
            $adminUser = User::where('email', $adminEmail)->first();

            if ($adminUser) {
                $adminUser->notify(new DailyRenewalSummary($summary));
                $this->info("Admin notification sent to {$adminEmail}");
            } else {
                $this->warn("Admin user not found: {$adminEmail}");
            }
        } catch (\Exception $e) {
            $this->error("Failed to send admin notification: " . $e->getMessage());
            Log::error('Failed to send admin notification', [
                'error' => $e->getMessage(),
                'summary' => $summary
            ]);
        }
    }

    /**
     * Process retry attempts for subscriptions with failed payments
     */
    private function processRetryAttempts(bool $isDryRun): void
    {
        $retrySubscriptions = VegboxSubscription::readyForRetry()->with('plan')->get();

        if ($retrySubscriptions->isEmpty()) {
            return;
        }

        $this->newLine();
        $this->info("Processing {$retrySubscriptions->count()} subscription payment retries...");

        $retrySuccessful = 0;
        $retryFailed = 0;

        foreach ($retrySubscriptions as $subscription) {
            $this->line("  Retry attempt #{$subscription->failed_payment_count} for subscription #{$subscription->id}");

            if ($isDryRun) {
                $this->line("  [DRY RUN] Would retry payment");
                continue;
            }

            $paymentResult = $this->paymentService->processSubscriptionRenewal($subscription);

            if ($paymentResult['success']) {
                $retrySuccessful++;
                $this->line("  ✓ Retry successful!");
            } else {
                $retryFailed++;
                $this->line("  ✗ Retry failed: {$paymentResult['error']}");

                // Check if max retries exceeded
                if ($subscription->hasExceededMaxRetries()) {
                    $this->warn("  ⚠ Max retries exceeded - will be cancelled after grace period");
                }
            }
        }

        if ($retrySuccessful > 0 || $retryFailed > 0) {
            $this->newLine();
            $this->info("Retry Summary: {$retrySuccessful} successful, {$retryFailed} failed");
        }
    }

    /**
     * Cancel subscriptions with expired grace periods
     */
    private function cancelExpiredGracePeriods(bool $isDryRun): void
    {
        $expiredSubscriptions = VegboxSubscription::gracePeriodExpired()->with('plan')->get();

        if ($expiredSubscriptions->isEmpty()) {
            return;
        }

        $this->newLine();
        $this->warn("Found {$expiredSubscriptions->count()} subscriptions with expired grace periods");

        foreach ($expiredSubscriptions as $subscription) {
            $this->line("  Cancelling subscription #{$subscription->id} (grace period ended)");

            if ($isDryRun) {
                $this->line("  [DRY RUN] Would cancel subscription");
                continue;
            }

            // Cancel the subscription
            $subscription->update([
                'canceled_at' => now(),
                'ends_at' => now(),
            ]);

            // Send cancellation notification
            $user = $subscription->subscriber;
            if ($user) {
                $reason = "Subscription cancelled due to payment failure after {$subscription->failed_payment_count} retry attempts";
                $user->notify(new \App\Notifications\SubscriptionCancelled($subscription, $reason, true));
            }

            Log::warning('Subscription auto-cancelled after grace period', [
                'subscription_id' => $subscription->id,
                'customer_id' => $subscription->subscriber_id,
                'failed_attempts' => $subscription->failed_payment_count,
                'last_error' => $subscription->last_payment_error
            ]);

            $this->line("  ✓ Subscription cancelled and customer notified");
        }
    }

    /**
     * Update WooCommerce renewal order with Stripe payment metadata
     */
    private function updateWooCommerceOrderStripeData(int $wooSubscriptionId, array $paymentResult): void
    {
        try {
            // Find the most recent renewal order for this subscription
            $renewalOrder = DB::connection('wordpress')
                ->table('D6sPMX_postmeta')
                ->where('meta_key', '_subscription_renewal')
                ->where('meta_value', (string)$wooSubscriptionId)
                ->orderBy('post_id', 'desc')
                ->first();

            if (!$renewalOrder) {
                Log::warning('No renewal order found to update with Stripe data', [
                    'woo_subscription_id' => $wooSubscriptionId
                ]);
                return;
            }

            $orderId = $renewalOrder->post_id;

            // Prepare Stripe metadata
            $stripeMetadata = [
                ['meta_key' => '_stripe_intent_id', 'meta_value' => $paymentResult['stripe_payment_intent']],
                ['meta_key' => '_transaction_id', 'meta_value' => $paymentResult['stripe_charge_id'] ?? $paymentResult['stripe_payment_intent']],
                ['meta_key' => '_payment_method', 'meta_value' => 'stripe'],
                ['meta_key' => '_payment_method_title', 'meta_value' => 'Credit / Debit Card'],
            ];

            if (!empty($paymentResult['stripe_charge_id'])) {
                $stripeMetadata[] = ['meta_key' => '_stripe_charge_id', 'meta_value' => $paymentResult['stripe_charge_id']];
            }

            // Insert or update metadata
            foreach ($stripeMetadata as $meta) {
                $existing = DB::connection('wordpress')
                    ->table('D6sPMX_postmeta')
                    ->where('post_id', $orderId)
                    ->where('meta_key', $meta['meta_key'])
                    ->first();

                if ($existing) {
                    DB::connection('wordpress')
                        ->table('D6sPMX_postmeta')
                        ->where('post_id', $orderId)
                        ->where('meta_key', $meta['meta_key'])
                        ->update(['meta_value' => $meta['meta_value']]);
                } else {
                    DB::connection('wordpress')
                        ->table('D6sPMX_postmeta')
                        ->insert([
                            'post_id' => $orderId,
                            'meta_key' => $meta['meta_key'],
                            'meta_value' => $meta['meta_value']
                        ]);
                }
            }

            Log::info('Updated WooCommerce order with Stripe metadata', [
                'order_id' => $orderId,
                'woo_subscription_id' => $wooSubscriptionId,
                'payment_intent' => $paymentResult['stripe_payment_intent']
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update WooCommerce order with Stripe data', [
                'woo_subscription_id' => $wooSubscriptionId,
                'error' => $e->getMessage()
            ]);
        }
    }
}
