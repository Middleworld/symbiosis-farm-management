<?php

namespace App\Services;

use Stripe\StripeClient;
use Stripe\Exception\ApiErrorException;
use Illuminate\Support\Facades\Log;

class StripePaymentService
{
    protected $stripe;
    protected $currency;

    public function __construct()
    {
        // Use config directly - no setting() helper needed
        $apiKey = config('services.stripe.secret');
        
        if (!$apiKey) {
            throw new \Exception('Stripe API key not configured. Please set STRIPE_SECRET in .env');
        }

        $this->stripe = new StripeClient($apiKey);
        $this->currency = config('services.stripe.currency', 'gbp');
    }

    /**
     * Create a Payment Intent for card payments
     * 
     * @param float $amount Amount in pounds/dollars (will be converted to pence/cents)
     * @param array $metadata Additional metadata for the payment
     * @return \Stripe\PaymentIntent
     */
    public function createPaymentIntent(float $amount, array $metadata = [])
    {
        try {
            // Convert to smallest currency unit (pence for GBP, cents for USD)
            $amountInCents = (int) ($amount * 100);

            $intent = $this->stripe->paymentIntents->create([
                'amount' => $amountInCents,
                'currency' => strtolower($this->currency),
                'payment_method_types' => ['card_present'],
                'capture_method' => 'automatic',
                'metadata' => array_merge([
                    'source' => 'POS Terminal',
                    'created_at' => now()->toISOString(),
                ], $metadata),
            ]);

            Log::info('Stripe Payment Intent created', [
                'payment_intent_id' => $intent->id,
                'amount' => $amount,
                'amount_cents' => $amountInCents,
            ]);

            return $intent;

        } catch (ApiErrorException $e) {
            Log::error('Stripe Payment Intent creation failed', [
                'error' => $e->getMessage(),
                'amount' => $amount,
            ]);
            throw new \Exception('Payment initialization failed: ' . $e->getMessage());
        }
    }

    /**
     * Confirm a Payment Intent
     * 
     * @param string $paymentIntentId
     * @param string $paymentMethodId
     * @return \Stripe\PaymentIntent
     */
    public function confirmPayment(string $paymentIntentId, string $paymentMethodId)
    {
        try {
            $intent = $this->stripe->paymentIntents->confirm($paymentIntentId, [
                'payment_method' => $paymentMethodId,
            ]);

            Log::info('Stripe Payment confirmed', [
                'payment_intent_id' => $intent->id,
                'status' => $intent->status,
            ]);

            return $intent;

        } catch (ApiErrorException $e) {
            Log::error('Stripe Payment confirmation failed', [
                'error' => $e->getMessage(),
                'payment_intent_id' => $paymentIntentId,
            ]);
            throw new \Exception('Payment confirmation failed: ' . $e->getMessage());
        }
    }

    /**
     * Retrieve a Payment Intent
     * 
     * @param string $paymentIntentId
     * @return \Stripe\PaymentIntent
     */
    public function getPaymentIntent(string $paymentIntentId)
    {
        try {
            return $this->stripe->paymentIntents->retrieve($paymentIntentId);
        } catch (ApiErrorException $e) {
            Log::error('Failed to retrieve Payment Intent', [
                'error' => $e->getMessage(),
                'payment_intent_id' => $paymentIntentId,
            ]);
            throw new \Exception('Failed to retrieve payment: ' . $e->getMessage());
        }
    }

    /**
     * Create a Terminal ConnectionToken for Stripe Terminal SDK
     * Required for Tap to Pay and Bluetooth readers
     * 
     * @return array
     */
    public function createConnectionToken()
    {
        try {
            $token = $this->stripe->terminal->connectionTokens->create();

            return [
                'secret' => $token->secret,
            ];

        } catch (ApiErrorException $e) {
            Log::error('Failed to create Stripe Terminal connection token', [
                'error' => $e->getMessage(),
            ]);
            throw new \Exception('Failed to initialize card reader: ' . $e->getMessage());
        }
    }

    /**
     * Create a Payment Link (optional fallback method)
     * 
     * @param float $amount
     * @param string $description
     * @param string|null $successUrl
     * @return \Stripe\PaymentLink
     */
    public function createPaymentLink(float $amount, string $description = 'POS Order', ?string $successUrl = null)
    {
        try {
            $amountInCents = (int) ($amount * 100);

            $link = $this->stripe->paymentLinks->create([
                'line_items' => [[
                    'price_data' => [
                        'currency' => strtolower($this->currency),
                        'product_data' => [
                            'name' => $description,
                        ],
                        'unit_amount' => $amountInCents,
                    ],
                    'quantity' => 1,
                ]],
                'after_completion' => [
                    'type' => 'redirect',
                    'redirect' => [
                        'url' => $successUrl ?? route('pos.index'),
                    ],
                ],
            ]);

            Log::info('Stripe Payment Link created', [
                'payment_link_id' => $link->id,
                'url' => $link->url,
                'amount' => $amount,
            ]);

            return $link;

        } catch (ApiErrorException $e) {
            Log::error('Failed to create Payment Link', [
                'error' => $e->getMessage(),
                'amount' => $amount,
            ]);
            throw new \Exception('Failed to create payment link: ' . $e->getMessage());
        }
    }

    /**
     * Refund a payment
     * 
     * @param string $paymentIntentId
     * @param float|null $amount Amount to refund (null for full refund)
     * @return \Stripe\Refund
     */
    public function refundPayment(string $paymentIntentId, ?float $amount = null)
    {
        try {
            $params = [
                'payment_intent' => $paymentIntentId,
            ];

            if ($amount !== null) {
                $params['amount'] = (int) ($amount * 100);
            }

            $refund = $this->stripe->refunds->create($params);

            Log::info('Stripe Refund created', [
                'refund_id' => $refund->id,
                'payment_intent_id' => $paymentIntentId,
                'amount' => $amount,
            ]);

            return $refund;

        } catch (ApiErrorException $e) {
            Log::error('Stripe Refund failed', [
                'error' => $e->getMessage(),
                'payment_intent_id' => $paymentIntentId,
            ]);
            throw new \Exception('Refund failed: ' . $e->getMessage());
        }
    }

    /**
     * Get Stripe publishable key for frontend
     * 
     * @return string
     */
    public function getPublishableKey(): string
    {
        return config('services.stripe.key') ?? '';
    }

    /**
     * Verify webhook signature
     * 
     * @param string $payload
     * @param string $signature
     * @return \Stripe\Event
     */
    public function verifyWebhook(string $payload, string $signature)
    {
        $webhookSecret = config('services.stripe.webhook_secret');

        if (!$webhookSecret) {
            throw new \Exception('Stripe webhook secret not configured');
        }

        try {
            return \Stripe\Webhook::constructEvent(
                $payload,
                $signature,
                $webhookSecret
            );
        } catch (\Exception $e) {
            Log::error('Stripe webhook verification failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
