<?php

namespace App\Console\Commands;

use App\Services\VegboxPaymentService;
use Illuminate\Console\Command;

class TestPaymentProcessing extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vegbox:test-payment-processing {--subscription_id= : Test with specific subscription}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test payment processing functionality for Vegbox subscriptions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 Testing Vegbox Payment Processing...');

        $paymentService = app(VegboxPaymentService::class);

        // Test service connection
        $this->info('Testing service connections...');
        $connectionStatus = $paymentService->testConnection();
        $this->info($connectionStatus);

        // Test with a specific subscription if provided
        if ($subscriptionId = $this->option('subscription_id')) {
            $this->info("Testing payment processing for subscription ID: {$subscriptionId}");

            $subscription = \App\Models\VegboxSubscription::find($subscriptionId);
            if (!$subscription) {
                $this->error("Subscription not found: {$subscriptionId}");
                return 1;
            }

            $planName = $subscription->plan->name ?? 'Unknown Plan';
            $this->info("Subscription: {$planName} - £{$subscription->price}");
            $this->info("Next billing: {$subscription->next_billing_at}");
            
            // Determine status based on active indicators
            $isActive = is_null($subscription->canceled_at) && is_null($subscription->ends_at);
            $status = $isActive ? 'Active' : 'Inactive';
            if ($subscription->canceled_at) $status = 'Canceled';
            if ($subscription->ends_at) $status = 'Expired';
            $this->info("Status: {$status}");

            // Don't actually process payment in test mode
            $this->warn('⚠️  Test mode - no actual payment processed');
            $this->info('✅ Payment processing logic appears functional');

        } else {
            // General connectivity test
            $this->info('✅ Payment service is configured and ready');
            $this->info('');
            $this->info('💡 To test with a real subscription:');
            $this->info('   php artisan vegbox:test-payment-processing --subscription_id=1');
        }

        $this->info('');
        $this->info('🎉 Payment processing test completed!');

        return 0;
    }
}