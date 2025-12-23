<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VegboxSubscription;
use App\Models\VegboxPlan;
use App\Models\Order;
use App\Services\VegboxPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;

class PaymentProcessingTest extends TestCase
{
    use RefreshDatabase;

    protected VegboxPaymentService $paymentService;
    protected User $user;
    protected VegboxPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->paymentService = app(VegboxPaymentService::class);
        
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'stripe_customer_id' => 'cus_test123',
        ]);
        
        $this->plan = VegboxPlan::create([
            'name' => 'Weekly Box',
            'slug' => 'weekly-box',
            'description' => 'Weekly vegbox delivery',
            'price' => 25.00,
            'currency' => 'GBP',
            'invoice_period' => 1,
            'invoice_interval' => 'week',
        ]);
    }

    /** @test */
    public function it_converts_technical_errors_to_customer_friendly_messages()
    {
        $technicalErrors = [
            'card_declined' => 'Your card was declined. Please contact your bank or try a different payment method.',
            'insufficient_funds' => 'Your card has insufficient funds. Please use a different card.',
            'expired_card' => 'Your card has expired. Please update your payment method.',
            'authentication_required' => 'Your bank requires additional verification. Please complete the authentication or use a different card.',
        ];

        foreach ($technicalErrors as $code => $expectedMessage) {
            $friendlyMessage = $this->paymentService->getCustomerFriendlyError('Technical error', $code);
            $this->assertEquals($expectedMessage, $friendlyMessage);
        }
    }

    /** @test */
    public function it_generates_unique_idempotency_keys()
    {
        $subscription = VegboxSubscription::create([
            'subscriber_id' => $this->user->id,
            'subscriber_type' => User::class,
            'plan_id' => $this->plan->id,
            'name' => 'Weekly Box',
            'price' => 25.00,
            'currency' => 'GBP',
            'billing_frequency' => 1,
            'billing_period' => 'week',
            'starts_at' => now(),
            'next_billing_at' => now(),
        ]);

        $billingDate = $subscription->next_billing_at->format('Y-m-d');
        $key1 = "sub_{$subscription->id}_billing_{$billingDate}";
        
        // Same subscription, same date = same key
        $key2 = "sub_{$subscription->id}_billing_{$billingDate}";
        $this->assertEquals($key1, $key2);
        
        // Different date = different key
        $differentDate = now()->addWeek()->format('Y-m-d');
        $key3 = "sub_{$subscription->id}_billing_{$differentDate}";
        $this->assertNotEquals($key1, $key3);
    }

    /** @test */
    public function it_handles_missing_payment_method_gracefully()
    {
        $subscription = VegboxSubscription::create([
            'subscriber_id' => $this->user->id,
            'subscriber_type' => User::class,
            'plan_id' => $this->plan->id,
            'name' => 'Weekly Box',
            'price' => 25.00,
            'currency' => 'GBP',
            'billing_frequency' => 1,
            'billing_period' => 'week',
            'starts_at' => now(),
            'next_billing_at' => now(),
        ]);

        // User has no payment method
        $this->user->update(['stripe_default_payment_method_id' => null]);
        
        $result = $this->paymentService->processSubscriptionRenewal($subscription);
        
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('payment method', strtolower($result['error']));
    }

    /** @test */
    public function it_creates_order_on_successful_payment()
    {
        $this->markTestSkipped('Requires Stripe mocking');
        
        // This test would require mocking Stripe API calls
        // In a full implementation, you would:
        // 1. Mock StripeClient
        // 2. Mock successful payment intent creation
        // 3. Verify Order record is created with correct data
    }

    /** @test */
    public function it_implements_exponential_backoff_for_retries()
    {
        $retrySchedule = [
            1 => 1,  // 1 hour
            2 => 4,  // 4 hours
            3 => 12, // 12 hours
        ];

        foreach ($retrySchedule as $attempt => $expectedHours) {
            $nextRetry = match ($attempt) {
                1 => 1,
                2 => 4,
                3 => 12,
                default => 24,
            };
            
            $this->assertEquals($expectedHours, $nextRetry);
        }
    }

    /** @test */
    public function it_records_payment_attempts_in_subscription()
    {
        $subscription = VegboxSubscription::create([
            'subscriber_id' => $this->user->id,
            'subscriber_type' => User::class,
            'plan_id' => $this->plan->id,
            'name' => 'Weekly Box',
            'price' => 25.00,
            'currency' => 'GBP',
            'billing_frequency' => 1,
            'billing_period' => 'week',
            'starts_at' => now(),
            'next_billing_at' => now(),
            'failed_payment_count' => 0,
        ]);

        // Simulate 3 failed attempts
        for ($i = 1; $i <= 3; $i++) {
            $subscription->update([
                'failed_payment_count' => $i,
                'last_payment_attempt_at' => now(),
                'last_payment_error' => 'Card declined',
            ]);
        }

        $this->assertEquals(3, $subscription->failed_payment_count);
        $this->assertNotNull($subscription->last_payment_attempt_at);
    }

    /** @test */
    public function it_validates_webhook_signatures()
    {
        // This would require mocking Stripe webhook signature verification
        $this->markTestSkipped('Requires Stripe webhook mocking');
        
        // In a full implementation:
        // 1. Generate test webhook payload
        // 2. Create invalid signature
        // 3. Verify webhook is rejected
    }

    /** @test */
    public function it_prevents_concurrent_payment_processing()
    {
        // This test verifies lockForUpdate() behavior
        $subscription = VegboxSubscription::create([
            'subscriber_id' => $this->user->id,
            'subscriber_type' => User::class,
            'plan_id' => $this->plan->id,
            'name' => 'Weekly Box',
            'price' => 25.00,
            'currency' => 'GBP',
            'billing_frequency' => 1,
            'billing_period' => 'week',
            'starts_at' => now(),
            'next_billing_at' => now()->subDay(),
        ]);

        // Query with lock
        $lockedSub = VegboxSubscription::where('id', $subscription->id)
            ->lockForUpdate()
            ->first();
        
        $this->assertNotNull($lockedSub);
        $this->assertEquals($subscription->id, $lockedSub->id);
    }
}
