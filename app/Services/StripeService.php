<?php

namespace App\Services;

use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Customer;
use Stripe\Charge;
use Stripe\Invoice;
use Stripe\Subscription;
use Stripe\StripeClient;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class StripeService
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Get payments with custom date range and pagination
     */
    public function getPayments($options = [])
    {
        try {
            $params = ['limit' => $options['limit'] ?? 25];
            
            // Add date range filter
            if (isset($options['start_date']) && isset($options['end_date'])) {
                $params['created'] = [
                    'gte' => Carbon::parse($options['start_date'])->startOfDay()->timestamp,
                    'lte' => Carbon::parse($options['end_date'])->endOfDay()->timestamp,
                ];
            } elseif (isset($options['days'])) {
                $params['created'] = [
                    'gte' => Carbon::now()->subDays($options['days'])->timestamp,
                ];
            }
            
            // Add pagination cursor
            if (isset($options['starting_after'])) {
                $params['starting_after'] = $options['starting_after'];
            }
            
            $charges = Charge::all($params);

            return [
                'data' => collect($charges->data)->map(function ($charge) {
                    return $this->formatCharge($charge);
                }),
                'has_more' => $charges->has_more,
                'last_id' => $charges->data ? end($charges->data)->id : null,
            ];
        } catch (\Exception $e) {
            Log::error('Stripe API Error: ' . $e->getMessage());
            return ['data' => collect([]), 'has_more' => false, 'last_id' => null];
        }
    }
    
    /**
     * Get recent payments from Stripe (legacy method)
     */
    public function getRecentPayments($limit = 25, $days = 30)
    {
        $result = $this->getPayments(['limit' => $limit, 'days' => $days]);
        return $result['data'];
    }
    
    /**
     * Format a charge object
     */
    private function formatCharge($charge)
    {
        // Try to get customer details if customer ID exists
        $customerName = 'Unknown';
        $customerEmail = $charge->billing_details->email ?? $charge->receipt_email;
        
        if ($charge->customer) {
            try {
                $customer = Customer::retrieve($charge->customer);
                $customerName = $customer->name ?? $customer->description ?? 'Unknown';
                $customerEmail = $customer->email ?? $customerEmail;
            } catch (\Exception $e) {
                // If customer fetch fails, use charge data
                $customerName = $charge->billing_details->name ?? 'Unknown';
            }
        } else {
            $customerName = $charge->billing_details->name ?? 'Unknown';
        }
        
        return [
            'id' => $charge->id,
            'amount' => $charge->amount / 100,
            'currency' => strtoupper($charge->currency),
            'status' => $charge->status,
            'customer_id' => $charge->customer,
            'customer_email' => $customerEmail,
            'customer_name' => $customerName,
            'description' => $charge->description,
            'created' => Carbon::createFromTimestamp($charge->created),
            'payment_method' => $this->getPaymentMethodInfo($charge),
            'refunded' => $charge->refunded,
            'amount_refunded' => $charge->amount_refunded / 100,
            'receipt_url' => $charge->receipt_url,
        ];
    }

    /**
     * Get payment statistics for dashboard
     */
    public function getPaymentStatistics($days = 30)
    {
        try {
            $charges = Charge::all([
                'limit' => 100,
                'created' => [
                    'gte' => Carbon::now()->subDays($days)->timestamp,
                ],
            ]);

            $payments = collect($charges->data);

            return [
                'total_revenue' => $payments->where('status', 'succeeded')->sum(function ($charge) {
                    return $charge->amount / 100;
                }),
                'total_transactions' => $payments->where('status', 'succeeded')->count(),
                'failed_transactions' => $payments->where('status', 'failed')->count(),
                'refunded_amount' => $payments->sum(function ($charge) {
                    return $charge->amount_refunded / 100;
                }),
                'average_transaction' => $payments->where('status', 'succeeded')->avg(function ($charge) {
                    return $charge->amount / 100;
                }),
                'top_customers' => $this->getTopCustomers($payments),
                'daily_revenue' => $this->getDailyRevenue($payments, $days),
            ];
        } catch (\Exception $e) {
            Log::error('Stripe Statistics Error: ' . $e->getMessage());
            return [
                'total_revenue' => 0,
                'total_transactions' => 0,
                'failed_transactions' => 0,
                'refunded_amount' => 0,
                'average_transaction' => 0,
                'top_customers' => [],
                'daily_revenue' => [],
            ];
        }
    }

    /**
     * Get subscription information
     */
    public function getSubscriptions($limit = 25)
    {
        try {
            $subscriptions = Subscription::all(['limit' => $limit]);

            return collect($subscriptions->data)->map(function ($subscription) {
                return [
                    'id' => $subscription->id,
                    'customer_id' => $subscription->customer,
                    'status' => $subscription->status,
                    'current_period_start' => Carbon::createFromTimestamp($subscription->current_period_start),
                    'current_period_end' => Carbon::createFromTimestamp($subscription->current_period_end),
                    'amount' => $subscription->items->data[0]->price->unit_amount / 100 ?? 0,
                    'currency' => strtoupper($subscription->items->data[0]->price->currency ?? 'USD'),
                    'interval' => $subscription->items->data[0]->price->recurring->interval ?? 'month',
                ];
            });
        } catch (\Exception $e) {
            Log::error('Stripe Subscriptions Error: ' . $e->getMessage());
            return collect([]);
        }
    }

    /**
     * Search for customer by email or name
     */
    public function searchCustomer($query)
    {
        try {
            $customers = Customer::all([
                'email' => $query,
                'limit' => 10
            ]);

            return collect($customers->data)->map(function ($customer) {
                return [
                    'id' => $customer->id,
                    'email' => $customer->email,
                    'name' => $customer->name ?? $customer->description,
                    'created' => Carbon::createFromTimestamp($customer->created),
                ];
            });
        } catch (\Exception $e) {
            Log::error('Stripe Customer Search Error: ' . $e->getMessage());
            return collect([]);
        }
    }

    private function getPaymentMethodInfo($charge)
    {
        if ($charge->payment_method_details) {
            $type = $charge->payment_method_details->type;
            switch ($type) {
                case 'card':
                    return [
                        'type' => 'Card',
                        'brand' => ucfirst($charge->payment_method_details->card->brand),
                        'last4' => $charge->payment_method_details->card->last4,
                    ];
                case 'bank_transfer':
                    return ['type' => 'Bank Transfer'];
                default:
                    return ['type' => ucfirst($type)];
            }
        }
        return ['type' => 'Unknown'];
    }

    private function getTopCustomers($payments)
    {
        $customerData = $payments->where('status', 'succeeded')
            ->groupBy('customer')
            ->map(function ($customerPayments, $customerId) {
                // Try to get customer name
                $customerName = 'Unknown';
                $customerEmail = 'Unknown';
                
                if ($customerId) {
                    try {
                        $customer = Customer::retrieve($customerId);
                        $customerName = $customer->name ?? $customer->description ?? 'Unknown';
                        $customerEmail = $customer->email ?? ($customerPayments->first()->receipt_email ?? 'Unknown');
                    } catch (\Exception $e) {
                        // Fallback to charge data
                        $firstCharge = $customerPayments->first();
                        $customerName = $firstCharge->billing_details->name ?? 'Unknown';
                        $customerEmail = $firstCharge->billing_details->email ?? $firstCharge->receipt_email ?? 'Unknown';
                    }
                } else {
                    $firstCharge = $customerPayments->first();
                    $customerName = $firstCharge->billing_details->name ?? 'Unknown';
                    $customerEmail = $firstCharge->billing_details->email ?? $firstCharge->receipt_email ?? 'Unknown';
                }
                
                return [
                    'customer_id' => $customerId,
                    'name' => $customerName,
                    'email' => $customerEmail,
                    'total' => $customerPayments->sum(function ($charge) {
                        return $charge->amount / 100;
                    }),
                    'count' => $customerPayments->count(),
                ];
            })
            ->sortByDesc('total')
            ->take(5)
            ->values();
        
        return $customerData;
    }

    private function getDailyRevenue($payments, $days)
    {
        $dailyRevenue = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $dayStart = Carbon::now()->subDays($i)->startOfDay()->timestamp;
            $dayEnd = Carbon::now()->subDays($i)->endOfDay()->timestamp;
            
            $dayRevenue = $payments->where('status', 'succeeded')
                ->filter(function ($charge) use ($dayStart, $dayEnd) {
                    return $charge->created >= $dayStart && $charge->created <= $dayEnd;
                })
                ->sum(function ($charge) {
                    return $charge->amount / 100;
                });
            
            $dailyRevenue[] = [
                'date' => $date,
                'revenue' => $dayRevenue
            ];
        }
        
        return $dailyRevenue;
    }
    
    /**
     * Get Stripe payouts with pagination
     */
    public function getPayouts($options = [])
    {
        try {
            $stripe = new StripeClient(config('services.stripe.secret'));
            
            $params = [
                'limit' => $options['limit'] ?? 25,
            ];
            
            // Add date range filter
            if (isset($options['start_date']) && isset($options['end_date'])) {
                $params['created'] = [
                    'gte' => Carbon::parse($options['start_date'])->startOfDay()->timestamp,
                    'lte' => Carbon::parse($options['end_date'])->endOfDay()->timestamp,
                ];
            } elseif (isset($options['days'])) {
                $params['created'] = [
                    'gte' => Carbon::now()->subDays($options['days'])->timestamp,
                ];
            }
            
            // Add pagination cursor
            if (isset($options['starting_after'])) {
                $params['starting_after'] = $options['starting_after'];
            }
            
            $payouts = $stripe->payouts->all($params);
            
            // Calculate total amount for the 30-day period
            $thirtyDaysAgo = Carbon::now()->subDays(30)->timestamp;
            $recentPayouts = $stripe->payouts->all([
                'limit' => 100,
                'created' => ['gte' => $thirtyDaysAgo]
            ]);
            
            $totalAmount = collect($recentPayouts->data)
                ->where('status', 'paid')
                ->sum('amount');

            return [
                'data' => $payouts->data,
                'has_more' => $payouts->has_more,
                'last_id' => $payouts->data ? end($payouts->data)->id : null,
                'total_amount' => $totalAmount,
            ];
        } catch (\Exception $e) {
            Log::error('Stripe Payouts Error: ' . $e->getMessage());
            return [
                'data' => [],
                'has_more' => false,
                'last_id' => null,
                'total_amount' => 0,
            ];
        }
    }
    
    /**
     * Get Stripe account balance
     */
    public function getAccountBalance()
    {
        try {
            $stripe = new StripeClient(config('services.stripe.secret'));
            $balance = $stripe->balance->retrieve();
            
            return [
                'available' => $balance->available,
                'pending' => $balance->pending,
                'livemode' => $balance->livemode,
            ];
        } catch (\Exception $e) {
            Log::error('Stripe Balance Error: ' . $e->getMessage());
            return [
                'available' => [],
                'pending' => [],
                'livemode' => false,
            ];
        }
    }
    
    /**
     * Get charges for a specific payout
     */
    public function getChargesForPayout($payoutId)
    {
        try {
            $stripe = new StripeClient(config('services.stripe.secret'));
            
            // Get balance transactions for this payout
            $transactions = $stripe->balanceTransactions->all([
                'payout' => $payoutId,
                'limit' => 100,
            ]);
            
            $charges = [];
            foreach ($transactions->data as $txn) {
                if ($txn->type === 'charge') {
                    try {
                        $charge = Charge::retrieve($txn->source);
                        $charges[] = $this->formatCharge($charge);
                    } catch (\Exception $e) {
                        // Skip if charge can't be retrieved
                        continue;
                    }
                }
            }
            
            return collect($charges);
        } catch (\Exception $e) {
            Log::error('Stripe Payout Charges Error: ' . $e->getMessage());
            return collect([]);
        }
    }

    /**
     * Get a payment intent by ID
     */
    public function getPaymentIntent($paymentIntentId)
    {
        try {
            return PaymentIntent::retrieve($paymentIntentId);
        } catch (\Exception $e) {
            Log::error('Stripe Get Payment Intent Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Refund a payment
     */
    public function refundPayment($paymentIntentId, $amount = null)
    {
        try {
            $params = ['payment_intent' => $paymentIntentId];
            
            if ($amount) {
                // Stripe expects amount in cents
                $params['amount'] = intval($amount * 100);
            }
            
            $refund = \Stripe\Refund::create($params);
            
            Log::info('Stripe refund created', [
                'refund_id' => $refund->id,
                'payment_intent' => $paymentIntentId,
                'amount' => $amount,
            ]);
            
            return [
                'id' => $refund->id,
                'amount' => $refund->amount / 100,
                'currency' => $refund->currency,
                'status' => $refund->status,
                'created' => $refund->created,
            ];
        } catch (\Exception $e) {
            Log::error('Stripe Refund Error: ' . $e->getMessage(), [
                'payment_intent' => $paymentIntentId,
                'amount' => $amount,
            ]);
            throw new \Exception('Refund failed: ' . $e->getMessage());
        }
    }

    /**
     * Verify Stripe webhook signature
     * 
     * @param string $payload Raw request body
     * @param string $signature Stripe-Signature header
     * @return \Stripe\Event
     * @throws \Stripe\Exception\SignatureVerificationException
     */
    public function verifyWebhook($payload, $signature)
    {
        $webhookSecret = config('services.stripe.webhook_secret');
        
        if (!$webhookSecret) {
            Log::warning('Stripe webhook secret not configured - signature verification skipped');
            // For development/testing only - decode payload without verification
            return json_decode($payload);
        }

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $signature,
                $webhookSecret
            );
            
            Log::info('Webhook signature verified successfully', [
                'event_id' => $event->id,
                'event_type' => $event->type
            ]);
            
            return $event;
            
        } catch(\UnexpectedValueException $e) {
            Log::error('Webhook payload invalid', ['error' => $e->getMessage()]);
            throw new \Exception('Invalid webhook payload');
            
        } catch(\Stripe\Exception\SignatureVerificationException $e) {
            Log::error('Webhook signature verification failed', [
                'error' => $e->getMessage(),
                'signature' => substr($signature, 0, 50) . '...'
            ]);
            throw $e;
        }
    }
}

