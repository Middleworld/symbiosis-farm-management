<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\VegboxSubscription;
use App\Notifications\LowBalanceWarning;
use App\Notifications\SubscriptionPaymentFailed;
use App\Notifications\SubscriptionRenewed;
use App\Services\VegboxPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Mockery;
use Tests\TestCase;

class VegboxPaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_insufficient_funds_without_stripe_triggers_warning(): void
    {
        $user = User::factory()->create();

        $subscription = new VegboxSubscription();
        $subscription->id = 77;
        $subscription->subscriber_id = $user->id;
        $subscription->price = 25;

        Notification::fake();

        $service = Mockery::mock(VegboxPaymentService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('checkCustomerBalance')->once()->andReturn([
            'success' => true,
            'balance' => 5,
        ]);
        $service->shouldReceive('canChargeWithStripe')->once()->andReturnFalse();

        $result = $service->processSubscriptionRenewal($subscription);

        $this->assertFalse($result['success']);
        $this->assertSame('INSUFFICIENT_FUNDS', $result['code']);

        Notification::assertSentTo($user, LowBalanceWarning::class);
    }

    public function test_stripe_fallback_successfully_charges_when_wallet_empty(): void
    {
        $user = User::factory()->create([
            'stripe_customer_id' => 'cus_test_123',
            'stripe_default_payment_method_id' => 'pm_card_visa',
        ]);

        $subscription = Mockery::mock(VegboxSubscription::class)->makePartial();
        $subscription->id = 501;
        $subscription->subscriber_id = $user->id;
        $subscription->price = 15.5;
        $subscription->currency = 'GBP';
        $subscription->setRelation('plan', (object) ['name' => 'Weekly Vegbox']);
        $subscription->shouldReceive('resetRetryTracking')->once();

        Notification::fake();

        $service = Mockery::mock(VegboxPaymentService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('checkCustomerBalance')->andReturn([
            'success' => true,
            'balance' => 0,
        ]);
        $service->shouldReceive('canChargeWithStripe')->andReturnTrue();
        $service->shouldReceive('chargeCustomerCard')->andReturn([
            'success' => true,
            'transaction_id' => 'pi_123',
        ]);

        $result = $service->processSubscriptionRenewal($subscription);

        $this->assertTrue($result['success']);
        $this->assertSame('card', $result['channel']);
        $this->assertSame('pi_123', $result['transaction_id']);

        Notification::assertSentTo($user, SubscriptionRenewed::class);
    }

    public function test_stripe_failure_notifies_customer(): void
    {
        $user = User::factory()->create([
            'stripe_customer_id' => 'cus_test_fail',
            'stripe_default_payment_method_id' => 'pm_fail',
        ]);

        $subscription = Mockery::mock(VegboxSubscription::class)->makePartial();
        $subscription->id = 999;
        $subscription->subscriber_id = $user->id;
        $subscription->price = 12;
        $subscription->shouldReceive('recordFailedPayment')->once();

        Notification::fake();

        $service = Mockery::mock(VegboxPaymentService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('checkCustomerBalance')->andReturn([
            'success' => true,
            'balance' => 0,
        ]);
        $service->shouldReceive('canChargeWithStripe')->andReturnTrue();
        $service->shouldReceive('chargeCustomerCard')->andReturn([
            'success' => false,
            'error' => 'Card declined',
            'code' => 'card_declined',
        ]);

        $result = $service->processSubscriptionRenewal($subscription);

        $this->assertFalse($result['success']);
        $this->assertSame('card_declined', $result['code']);

        Notification::assertSentTo($user, SubscriptionPaymentFailed::class);
    }
}
