<?php

namespace App\Services;

use Stripe\Stripe;
use Stripe\Terminal\ConnectionToken;
use Stripe\Terminal\Location;
use Stripe\Terminal\Reader;
use Stripe\PaymentIntent;
use Illuminate\Support\Facades\Log;
use Exception;

class PosPaymentService
{
    protected $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Create a connection token for Stripe Terminal
     */
    public function createConnectionToken()
    {
        try {
            $token = ConnectionToken::create();
            return [
                'success' => true,
                'secret' => $token->secret
            ];
        } catch (Exception $e) {
            Log::error('Stripe Terminal Connection Token Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get or create a terminal location
     */
    public function getOrCreateLocation($displayName = 'MWF POS Terminal')
    {
        try {
            // Try to find existing location
            $locations = Location::all(['limit' => 10]);
            foreach ($locations->data as $location) {
                if ($location->display_name === $displayName) {
                    return $location;
                }
            }

            // Create new location if not found
            return Location::create([
                'display_name' => $displayName,
                'address' => [
                    'line1' => 'Middleworld Farm',
                    'city' => 'Unknown',
                    'country' => 'GB',
                    'postal_code' => 'Unknown',
                ],
            ]);
        } catch (Exception $e) {
            Log::error('Stripe Terminal Location Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Register a card reader
     */
    public function registerReader($registrationCode, $label = 'POS Reader')
    {
        try {
            $location = $this->getOrCreateLocation();

            $reader = Reader::create([
                'registration_code' => $registrationCode,
                'label' => $label,
                'location' => $location->id,
            ]);

            return [
                'success' => true,
                'reader' => $reader
            ];
        } catch (Exception $e) {
            Log::error('Stripe Reader Registration Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get registered readers
     */
    public function getReaders()
    {
        try {
            $readers = Reader::all(['limit' => 10]);
            return [
                'success' => true,
                'readers' => $readers->data
            ];
        } catch (Exception $e) {
            Log::error('Stripe Get Readers Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'readers' => []
            ];
        }
    }

    /**
     * Create a payment intent for POS
     */
    public function createPaymentIntent($amount, $currency = 'gbp', $metadata = [])
    {
        try {
            $paymentIntent = PaymentIntent::create([
                'amount' => (int)($amount * 100), // Convert to pence
                'currency' => $currency,
                'payment_method_types' => ['card_present'],
                'capture_method' => 'automatic',
                'metadata' => array_merge($metadata, [
                    'source' => 'pos_system',
                    'created_at' => now()->toISOString()
                ])
            ]);

            return [
                'success' => true,
                'payment_intent' => $paymentIntent,
                'client_secret' => $paymentIntent->client_secret
            ];
        } catch (Exception $e) {
            Log::error('Stripe Payment Intent Creation Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Process a card payment through reader
     */
    public function processCardPayment($readerId, $amount, $orderId = null)
    {
        try {
            // Create payment intent
            $paymentIntent = $this->createPaymentIntent($amount);

            if (!$paymentIntent['success']) {
                return $paymentIntent;
            }

            // In a real implementation, you would:
            // 1. Send the payment intent to the reader
            // 2. Wait for card interaction
            // 3. Process the payment
            // 4. Confirm completion

            // For now, simulate the process
            Log::info('POS Card Payment Initiated', [
                'reader_id' => $readerId,
                'amount' => $amount,
                'order_id' => $orderId,
                'payment_intent_id' => $paymentIntent['payment_intent']->id
            ]);

            return [
                'success' => true,
                'payment_intent_id' => $paymentIntent['payment_intent']->id,
                'status' => 'pending',
                'message' => 'Payment initiated. Waiting for card interaction.'
            ];

        } catch (Exception $e) {
            Log::error('POS Card Payment Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Simulate NFC/Contactless payment using phone
     */
    public function processContactlessPayment($amount, $orderId = null)
    {
        try {
            // For contactless payments, we can use Stripe's card_present method
            // In practice, this would integrate with the phone's NFC capabilities
            // via Web NFC API or a mobile app

            $paymentIntent = $this->createPaymentIntent($amount, 'gbp', [
                'payment_type' => 'contactless',
                'order_id' => $orderId
            ]);

            Log::info('POS Contactless Payment Initiated', [
                'amount' => $amount,
                'order_id' => $orderId,
                'payment_intent_id' => $paymentIntent['payment_intent']->id ?? null
            ]);

            return $paymentIntent;

        } catch (Exception $e) {
            Log::error('POS Contactless Payment Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check payment status
     */
    public function getPaymentStatus($paymentIntentId)
    {
        try {
            $paymentIntent = PaymentIntent::retrieve($paymentIntentId);

            return [
                'success' => true,
                'status' => $paymentIntent->status,
                'amount' => $paymentIntent->amount / 100,
                'currency' => $paymentIntent->currency,
                'payment_method' => $paymentIntent->payment_method,
                'captured' => $paymentIntent->status === 'succeeded',
                'metadata' => $paymentIntent->metadata
            ];
        } catch (Exception $e) {
            Log::error('Payment Status Check Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get supported payment methods for POS
     */
    public function getSupportedPaymentMethods()
    {
        $config = config('pos_payments.payment_methods', []);

        return collect($config)->filter(function ($method) {
            return $method['enabled'] ?? false;
        })->map(function ($method, $key) {
            return [
                'id' => $key,
                'name' => $method['name'],
                'icon' => $method['icon'],
                'requires_confirmation' => $method['requires_confirmation'] ?? false,
                'processor' => $method['processor'] ?? 'stripe'
            ];
        })->values();
    }

    /**
     * Validate payment amount
     */
    public function validatePaymentAmount($amount)
    {
        $maxAmount = config('pos_payments.security.max_transaction_amount', 1000.00);

        if ($amount > $maxAmount) {
            return [
                'valid' => false,
                'error' => "Payment amount exceeds maximum allowed (£{$maxAmount})"
            ];
        }

        if ($amount <= 0) {
            return [
                'valid' => false,
                'error' => 'Payment amount must be greater than zero'
            ];
        }

        return ['valid' => true];
    }
}