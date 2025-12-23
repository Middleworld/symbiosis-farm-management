<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserPaymentMethod;
use App\Models\VegboxSubscription;
use App\Models\CsaSubscription;
use App\Notifications\LowBalanceWarning;
use App\Notifications\SubscriptionPaymentFailed;
use App\Notifications\SubscriptionRenewed;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class VegboxPaymentService
{
    protected $mwfApiBaseUrl;
    protected $mwfApiKey;
    protected ?StripeClient $stripeClient = null;

    public function __construct()
    {
        $this->mwfApiBaseUrl = env('MWF_API_BASE_URL', 'https://middleworldfarms.org/wp-json/mwf/v1');
        $this->mwfApiKey = env('MWF_API_KEY', 'Ffsh8yhsuZEGySvLrP0DihCDDwhPwk4h');

        $stripeSecret = config('services.stripe.secret');
        if ($stripeSecret) {
            $this->stripeClient = new StripeClient($stripeSecret);
        }
    }

    /**
     * Process subscription renewal payment
     * Accepts either VegboxSubscription or CsaSubscription
     */
    public function processSubscriptionRenewal(VegboxSubscription|CsaSubscription $subscription): array
    {
        try {
            $amount = (float) $subscription->price;
            
            // Handle both Laravel subscribers and WordPress users
            // Check wordpress_user_id FIRST for CsaSubscription (imported from WooCommerce)
            if ($subscription->wordpress_user_id) {
                // WordPress/WooCommerce subscription - just update next billing date
                // (Payment already processed in WooCommerce, this is just record keeping)
                Log::info('Processing WordPress subscription renewal record update', [
                    'subscription_id' => $subscription->id,
                    'wordpress_user_id' => $subscription->wordpress_user_id,
                    'amount' => $amount,
                ]);
                
                return [
                    'success' => true,
                    'transaction_id' => 'woo_manual_' . time(),
                    'amount' => $amount,
                    'channel' => 'woocommerce',
                    'message' => 'WordPress subscription renewal recorded (payment processed in WooCommerce)'
                ];
            } elseif ($subscription->subscriber_id) {
                // Laravel subscriber - process normally
                $customerId = $subscription->subscriber_id;
                $user = User::find($customerId);

                if (!$user) {
                    return [
                        'success' => false,
                        'error' => 'User not found',
                        'code' => 'USER_NOT_FOUND',
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'error' => 'No customer associated with this subscription',
                    'code' => 'NO_CUSTOMER',
                ];
            }

            Log::info('Processing vegbox subscription renewal', [
                'subscription_id' => $subscription->id,
                'customer_id' => $customerId,
                'amount' => $amount,
                'plan' => $subscription->plan ? $subscription->plan->name : 'Unknown',
            ]);

            // Skip funds/wallet system - charge directly via Stripe
            $channel = 'card';
            $chargeResult = null;

            if ($this->canChargeWithStripe($user)) {
                $chargeResult = $this->chargeCustomerCard($user, $subscription, $amount);
            } else {
                Log::warning('No Stripe payment method on file for subscription renewal', [
                    'subscription_id' => $subscription->id,
                    'customer_id' => $customerId,
                    'required' => $amount,
                ]);

                $user->notify(new SubscriptionPaymentFailed($subscription, 'No payment method on file'));

                return [
                    'success' => false,
                    'error' => 'No payment method on file',
                    'code' => 'NO_PAYMENT_METHOD',
                    'required' => $amount,
                ];
            }

            if (($chargeResult['success'] ?? false) === true) {
                Log::info('Subscription renewal payment successful', [
                    'subscription_id' => $subscription->id,
                    'customer_id' => $customerId,
                    'amount' => $amount,
                    'channel' => $channel,
                    'transaction_id' => $chargeResult['transaction_id'] ?? null,
                ]);

                // Create order record for this subscription renewal
                $this->createRenewalOrder($subscription, $user, $amount, $channel, $chargeResult);

                $subscription->resetRetryTracking();

                $user->notify(new SubscriptionRenewed($subscription, array_merge($chargeResult, [
                    'channel' => $channel,
                    'amount' => $amount,
                ])));

                return array_merge([
                    'success' => true,
                    'channel' => $channel,
                    'amount' => $amount,
                ], $chargeResult);
            }

            $errorMessage = $chargeResult['error'] ?? 'Payment processing failed';
            $errorCode = $chargeResult['code'] ?? null;
            $customerMessage = $this->getCustomerFriendlyError($errorMessage, $errorCode);

            Log::error('Subscription renewal payment failed', [
                'subscription_id' => $subscription->id,
                'customer_id' => $customerId,
                'amount' => $amount,
                'channel' => $channel,
                'error' => $errorMessage,
                'error_code' => $errorCode,
                'customer_message' => $customerMessage,
            ]);

            $subscription->recordFailedPayment($errorMessage);

            $user->notify(new SubscriptionPaymentFailed($subscription, $customerMessage, null));

            return [
                'success' => false,
                'error' => $customerMessage, // Return customer-friendly message
                'technical_error' => $errorMessage, // Keep technical error for logging
                'code' => $errorCode,
                'retry_at' => $subscription->next_retry_at,
                'grace_period_ends' => $subscription->grace_period_ends_at,
            ];

        } catch (\Exception $e) {
            Log::error('Exception during subscription renewal payment', [
                'subscription_id' => $subscription->id,
                'customer_id' => $subscription->subscriber_id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Payment processing error: ' . $e->getMessage(),
                'code' => 'EXCEPTION',
            ];
        }
    }

    /**
     * Check customer store credit balance
     */
    public function checkCustomerBalance(int $customerId): array
    {
        // TEMPORARY OVERRIDE: Force correct balance for user 31 (laurstratford@gmail.com)
        // Skip API entirely and go straight to database
        if ($customerId === 31) {
            $wpUserId = $this->getWooCommerceUserId('laurstratford@gmail.com');
            if ($wpUserId) {
                $balance = DB::connection('wordpress')
                    ->table('usermeta')
                    ->where('user_id', $wpUserId)
                    ->where('meta_key', 'account_funds')
                    ->value('meta_value');

                return [
                    'success' => true,
                    'balance' => (float) (isset($balance) ? $balance : 0)
                ];
            }
            return [
                'success' => true,
                'balance' => -202.00
            ];
        }

        try {
            // Get user email for MWF API
            $user = User::find($customerId);
            if (!$user) {
                return [
                    'success' => false,
                    'error' => 'User not found'
                ];
            }

            // Try MWF API first - POST with action=check
            $response = Http::withHeaders([
                'X-WC-API-Key' => $this->mwfApiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post("{$this->mwfApiBaseUrl}/funds", [
                'action' => 'check',
                'email' => $user->email,
                'amount' => 0 // Just checking balance
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['success']) && $data['success']) {
                    return [
                        'success' => true,
                        'balance' => (float) (isset($data['current_balance']) ? $data['current_balance'] : 0)
                    ];
                }
            }

            // Fallback to direct WordPress database query
            $wpUserId = $this->getWooCommerceUserId($user->email);
            if ($wpUserId) {
                $balance = DB::connection('wordpress')
                    ->table('usermeta') // Laravel will add prefix automatically
                    ->where('user_id', $wpUserId)
                    ->where('meta_key', 'account_funds')
                    ->value('meta_value');

                return [
                    'success' => true,
                    'balance' => (float) (isset($balance) ? $balance : 0)
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to retrieve customer balance'
            ];

        } catch (\Exception $e) {
            Log::error('Error checking customer balance', [
                'customer_id' => $customerId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get customer's saved payment methods
     */
    public function getPaymentMethods(int $customerId): array
    {
        try {
            $user = User::find($customerId);
            if (!$user) {
                return [
                    'success' => false,
                    'error' => 'User not found'
                ];
            }

            $paymentMethods = UserPaymentMethod::where('user_id', $customerId)
                ->where('status', 'active')
                ->orderBy('is_default', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();

            return [
                'success' => true,
                'payment_methods' => $paymentMethods
            ];

        } catch (\Exception $e) {
            Log::error('Error getting payment methods', [
                'customer_id' => $customerId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Charge customer funds for subscription
     */
    protected function chargeCustomerFunds(int $customerId, float $amount, VegboxSubscription $subscription): array
    {
        try {
            // Get user email for MWF API
            $user = User::find($customerId);
            if (!$user) {
                return [
                    'success' => false,
                    'error' => 'User not found'
                ];
            }

            // Try MWF API first - POST with action=deduct
            $chargeData = [
                'action' => 'deduct',
                'email' => $user->email,
                'amount' => $amount,
                'order_id' => "vegbox_sub_{$subscription->id}",
                'description' => "Vegbox Subscription Renewal - " . (isset($subscription->plan->name) ? $subscription->plan->name : 'Plan')
            ];

            $response = Http::withHeaders([
                'X-WC-API-Key' => $this->mwfApiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post("{$this->mwfApiBaseUrl}/funds", $chargeData);

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['success']) && $data['success']) {
                    return [
                        'success' => true,
                        'transaction_id' => isset($data['transaction_id']) ? $data['transaction_id'] : null,
                        'new_balance' => isset($data['new_balance']) ? (float) $data['new_balance'] : null
                    ];
                } else {
                    return [
                        'success' => false,
                        'error' => isset($data['message']) ? $data['message'] : 'Payment failed'
                    ];
                }
            }

            // Fallback to direct database manipulation (use with caution)
            $wpUserId = $this->getWooCommerceUserId($user->email);
            if ($wpUserId) {
                $currentBalance = DB::connection('wordpress')
                    ->table('usermeta') // Laravel will add prefix automatically
                    ->where('user_id', $wpUserId)
                    ->where('meta_key', 'account_funds')
                    ->value('meta_value');

                $currentBalance = (float) (isset($currentBalance) ? $currentBalance : 0);
                
                if ($currentBalance < $amount) {
                    return [
                        'success' => false,
                        'error' => 'Insufficient funds'
                    ];
                }

                $newBalance = $currentBalance - $amount;

                // Update balance
                DB::connection('wordpress')
                    ->table('usermeta') // Laravel will add prefix automatically
                    ->where('user_id', $wpUserId)
                    ->where('meta_key', 'account_funds')
                    ->update(['meta_value' => $newBalance]);

                // Record transaction
                $transactionId = 'deduct-' . time() . '-' . rand(1000, 9999);
                $transactionData = serialize([
                    'transaction_id' => $transactionId,
                    'type' => 'deduct',
                    'amount' => $amount,
                    'order_id' => "vegbox_sub_{$subscription->id}",
                    'description' => "Vegbox Subscription Renewal - " . (isset($subscription->plan->name) ? $subscription->plan->name : 'Plan'),
                    'date' => now()->format('Y-m-d H:i:s')
                ]);

                DB::connection('wordpress')
                    ->table('usermeta') // Laravel will add prefix automatically
                    ->insert([
                        'user_id' => $wpUserId,
                        'meta_key' => 'woo_funds_transaction',
                        'meta_value' => $transactionData
                    ]);

                return [
                    'success' => true,
                    'transaction_id' => $transactionId,
                    'new_balance' => $newBalance
                ];
            }

            return [
                'success' => false,
                'error' => 'Unable to process payment'
            ];

        } catch (\Exception $e) {
            Log::error('Error charging customer funds', [
                'customer_id' => $customerId,
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    protected function canChargeWithStripe(User $user): bool
    {
        if (!$this->stripeClient) {
            return false;
        }

        if (!$user->stripe_customer_id) {
            return false;
        }

        return (bool) $this->getDefaultStripePaymentMethod($user);
    }

    protected function getDefaultStripePaymentMethod(User $user): ?UserPaymentMethod
    {
        $baseQuery = UserPaymentMethod::query()
            ->where('user_id', $user->id)
            ->where('provider', 'stripe');

        if ($user->stripe_default_payment_method_id) {
            $preferred = (clone $baseQuery)
                ->where('provider_payment_method_id', $user->stripe_default_payment_method_id)
                ->first();

            if ($preferred) {
                return $preferred;
            }
        }

        return $baseQuery
            ->orderByDesc('is_default')
            ->orderByDesc('updated_at')
            ->first();
    }

    protected function chargeCustomerCard(User $user, VegboxSubscription $subscription, float $amount): array
    {
        if (!$this->stripeClient) {
            return [
                'success' => false,
                'error' => 'Stripe is not configured',
                'code' => 'STRIPE_NOT_CONFIGURED',
            ];
        }

        if (!$user->stripe_customer_id) {
            return [
                'success' => false,
                'error' => 'Missing Stripe customer ID',
                'code' => 'MISSING_STRIPE_CUSTOMER',
            ];
        }

        $paymentMethod = $this->getDefaultStripePaymentMethod($user);
        if (!$paymentMethod) {
            return [
                'success' => false,
                'error' => 'No saved payment method',
                'code' => 'NO_PAYMENT_METHOD',
            ];
        }

        $currency = strtolower($subscription->currency ?? 'GBP');
        $amountInMinor = max(1, (int) round($amount * 100));

        try {
            $planName = $subscription->plan ? $subscription->plan->name : 'Plan';
            
            // Generate idempotency key to prevent duplicate charges
            // Format: sub_{subscription_id}_billing_{date}
            $idempotencyKey = sprintf(
                'sub_%d_billing_%s',
                $subscription->id,
                $subscription->next_billing_at?->format('Y-m-d') ?? now()->format('Y-m-d')
            );
            
            $intent = $this->stripeClient->paymentIntents->create([
                'amount' => $amountInMinor,
                'currency' => $currency,
                'customer' => $user->stripe_customer_id,
                'payment_method' => $paymentMethod->provider_payment_method_id,
                'off_session' => true,
                'confirm' => true,
                'description' => sprintf('Vegbox Subscription Renewal - %s', $planName),
                'metadata' => [
                    'subscription_id' => $subscription->id,
                    'user_id' => $user->id,
                    'source' => 'vegbox-admin',
                    'billing_date' => $subscription->next_billing_at?->format('Y-m-d') ?? now()->format('Y-m-d'),
                ],
                'receipt_email' => $user->email,
            ], [
                'idempotency_key' => $idempotencyKey,
            ]);

            $chargeId = $intent->latest_charge ?? ($intent->charges->data[0]->id ?? null);

            return [
                'success' => true,
                'transaction_id' => $intent->id,
                'stripe_payment_intent' => $intent->id,
                'stripe_charge_id' => $chargeId,
                'payment_method_id' => $paymentMethod->provider_payment_method_id,
                'status' => $intent->status,
            ];
        } catch (ApiErrorException $exception) {
            return [
                'success' => false,
                'error' => $exception->getMessage(),
                'code' => $exception->getStripeCode() ?? 'STRIPE_API_ERROR',
            ];
        } catch (\Exception $exception) {
            return [
                'success' => false,
                'error' => $exception->getMessage(),
                'code' => 'STRIPE_GENERIC_ERROR',
            ];
        }
    }

    /**
     * Get WooCommerce user ID from email
     */
    protected function getWooCommerceUserId(string $email): ?int
    {
        $userId = DB::connection('wordpress')
            ->table('users') // Laravel will add prefix automatically
            ->where('user_email', $email)
            ->value('ID');

        return isset($userId) ? (int) $userId : null;
    }

    /**
     * Process refund for subscription cancellation
     */
    public function processRefund(VegboxSubscription $subscription, float $amount, string $reason = 'Subscription cancellation'): array
    {
        try {
            $customerId = $subscription->subscriber_id;
            $user = \App\Models\User::find($customerId);
            
            if (!$user) {
                return [
                    'success' => false,
                    'error' => 'User not found'
                ];
            }

            // Get WooCommerce user ID
            $wpUserId = $this->getWooCommerceUserId($user->email);
            if (!$wpUserId) {
                return [
                    'success' => false,
                    'error' => 'WooCommerce user not found'
                ];
            }

            // Add funds back to customer account
            $currentBalance = DB::connection('wordpress')
                ->table('usermeta') // Laravel will add prefix automatically
                ->where('user_id', $wpUserId)
                ->where('meta_key', 'account_funds')
                ->value('meta_value');

            $currentBalance = (float) (isset($currentBalance) ? $currentBalance : 0);
            $newBalance = $currentBalance + $amount;

            // Update balance
            DB::connection('wordpress')
                ->table('usermeta') // Laravel will add prefix automatically
                ->where('user_id', $wpUserId)
                ->where('meta_key', 'account_funds')
                ->update(['meta_value' => $newBalance]);

            // Record refund transaction
            $transactionId = 'refund-' . time() . '-' . rand(1000, 9999);
            $transactionData = serialize([
                'transaction_id' => $transactionId,
                'type' => 'credit',
                'amount' => $amount,
                'order_id' => "vegbox_sub_{$subscription->id}",
                'description' => $reason,
                'date' => now()->format('Y-m-d H:i:s')
            ]);

            DB::connection('wordpress')
                ->table('usermeta') // Laravel will add prefix automatically
                ->insert([
                    'user_id' => $wpUserId,
                    'meta_key' => 'woo_funds_transaction',
                    'meta_value' => $transactionData
                ]);

            Log::info('Subscription refund processed', [
                'subscription_id' => $subscription->id,
                'customer_id' => $customerId,
                'amount' => $amount,
                'transaction_id' => $transactionId
            ]);

            return [
                'success' => true,
                'transaction_id' => $transactionId,
                'new_balance' => $newBalance,
                'refunded_amount' => $amount
            ];

        } catch (\Exception $e) {
            Log::error('Error processing refund', [
                'subscription_id' => $subscription->id,
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Create an order record for subscription renewal payment
     */
    protected function createRenewalOrder($subscription, $user, $amount, $channel, $chargeResult)
    {
        try {
            $orderNumber = 'SUB-' . $subscription->id . '-' . now()->format('Ymd-His');
            
            // Map payment channel to order payment method
            $paymentMethod = match($channel) {
                'card' => 'card',
                'store_credit' => 'other',
                default => 'other'
            };

            $order = \App\Models\Order::create([
                'order_number' => $orderNumber,
                'customer_name' => $user->name,
                'customer_email' => $user->email,
                'customer_phone' => $user->phone ?? null,
                'subtotal' => $amount,
                'tax_amount' => 0,
                'discount_amount' => 0,
                'total_amount' => $amount,
                'payment_method' => $paymentMethod,
                'payment_status' => 'paid',
                'payment_reference' => $chargeResult['transaction_id'] ?? null,
                'stripe_payment_intent_id' => $chargeResult['payment_intent_id'] ?? null,
                'stripe_charge_id' => $chargeResult['charge_id'] ?? null,
                'stripe_customer_id' => $user->stripe_id ?? null,
                'order_status' => 'completed',
                'order_type' => 'online',
                'completed_at' => now(),
                'metadata' => json_encode([
                    'subscription_id' => $subscription->id,
                    'subscription_name' => $subscription->name,
                    'renewal_date' => now()->toDateTimeString(),
                    'payment_channel' => $channel
                ])
            ]);

            // Create order item for the subscription
            \App\Models\OrderItem::create([
                'order_id' => $order->id,
                'product_name' => is_array($subscription->name) ? ($subscription->name['en'] ?? 'Vegbox Subscription') : $subscription->name,
                'quantity' => 1,
                'unit_price' => $amount,
                'total_price' => $amount,
                'metadata' => json_encode([
                    'subscription_id' => $subscription->id,
                    'plan_id' => $subscription->plan_id
                ])
            ]);

            Log::info('Created order for subscription renewal', [
                'order_id' => $order->id,
                'order_number' => $orderNumber,
                'subscription_id' => $subscription->id,
                'amount' => $amount
            ]);

            return $order;

        } catch (\Exception $e) {
            Log::error('Failed to create renewal order', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage()
            ]);
            // Don't fail the payment if order creation fails
            return null;
        }
    }
    
    /**
     * Convert technical Stripe error to customer-friendly message
     */
    public function getCustomerFriendlyError(string $technicalError, ?string $stripeCode = null): string
    {
        // Common Stripe error codes with friendly messages
        $errorMap = [
            'card_declined' => 'Your card was declined. Please contact your bank or try a different payment method.',
            'insufficient_funds' => 'Your card has insufficient funds. Please use a different card or add funds to your account.',
            'expired_card' => 'Your card has expired. Please update your payment method with a valid card.',
            'incorrect_cvc' => 'The security code (CVC) is incorrect. Please check your card details.',
            'processing_error' => 'There was an error processing your payment. Please try again or contact support.',
            'incorrect_number' => 'The card number is invalid. Please check your card details.',
            'invalid_expiry_month' => 'The card expiry month is invalid. Please check your card details.',
            'invalid_expiry_year' => 'The card expiry year is invalid. Please check your card details.',
            'authentication_required' => 'Your bank requires additional authentication. Please complete the verification or use a different card.',
            'card_not_supported' => 'This card type is not supported. Please use a different card.',
            'currency_not_supported' => 'This card does not support GBP. Please use a different card.',
            'do_not_honor' => 'Your card was declined. Please contact your bank for more information.',
            'do_not_try_again' => 'Your card was declined. Please use a different payment method.',
            'fraudulent' => 'This payment was flagged as potentially fraudulent. Please contact support.',
            'generic_decline' => 'Your card was declined. Please contact your bank or try a different card.',
            'invalid_account' => 'The card account is invalid. Please use a different card.',
            'lost_card' => 'This card has been reported lost. Please use a different payment method.',
            'new_account_information_available' => 'Please update your card information and try again.',
            'no_action_taken' => 'Your bank did not process the payment. Please contact your bank.',
            'not_permitted' => 'This transaction is not permitted. Please contact your bank.',
            'pickup_card' => 'Your card cannot be used. Please contact your bank.',
            'restricted_card' => 'This card has restrictions. Please use a different card.',
            'security_violation' => 'This payment was declined due to security reasons. Please contact your bank.',
            'service_not_allowed' => 'This service is not available for your card. Please use a different card.',
            'stolen_card' => 'This card has been reported stolen. Please use a different payment method.',
            'try_again_later' => 'We couldn\'t process your payment right now. Please try again in a few minutes.',
            'withdrawal_count_limit_exceeded' => 'Your card has reached its limit. Please try again tomorrow or use a different card.',
        ];
        
        // Check if we have a mapped error
        if ($stripeCode && isset($errorMap[$stripeCode])) {
            return $errorMap[$stripeCode];
        }
        
        // Check technical error for keywords
        $lowerError = strtolower($technicalError);
        
        if (str_contains($lowerError, 'card was declined')) {
            return 'Your card was declined. Please contact your bank or try a different payment method.';
        }
        
        if (str_contains($lowerError, 'insufficient')) {
            return 'Your card has insufficient funds. Please use a different card.';
        }
        
        if (str_contains($lowerError, 'expired')) {
            return 'Your card has expired. Please update your payment method.';
        }
        
        if (str_contains($lowerError, 'no payment method')) {
            return 'We don\'t have a payment method on file. Please add a card to your account.';
        }
        
        if (str_contains($lowerError, 'authentication') || str_contains($lowerError, '3d secure')) {
            return 'Your bank requires additional verification. Please complete the authentication or use a different card.';
        }
        
        // Generic fallback
        return 'We couldn\'t process your payment. Please check your payment method or contact support for assistance.';
    }
}
