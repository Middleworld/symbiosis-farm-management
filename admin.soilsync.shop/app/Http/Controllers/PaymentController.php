<?php

namespace App\Http\Controllers;

use App\Services\StripePaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected $stripeService;

    public function __construct(StripePaymentService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    /**
     * Create a payment intent
     * POST /api/payments/intent
     */
    public function createIntent(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'order_id' => 'nullable|integer',
            'customer_name' => 'nullable|string',
            'items' => 'nullable|array',
        ]);

        try {
            $metadata = [
                'order_id' => $request->order_id,
                'customer_name' => $request->customer_name,
                'items_count' => is_array($request->items) ? count($request->items) : 0,
            ];

            $intent = $this->stripeService->createPaymentIntent(
                $request->amount,
                array_filter($metadata) // Remove null values
            );

            return response()->json([
                'success' => true,
                'client_secret' => $intent->client_secret,
                'payment_intent_id' => $intent->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Payment Intent creation failed', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Confirm a payment
     * POST /api/payments/confirm
     */
    public function confirmPayment(Request $request)
    {
        $request->validate([
            'payment_intent_id' => 'required|string',
            'payment_method_id' => 'required|string',
        ]);

        try {
            $intent = $this->stripeService->confirmPayment(
                $request->payment_intent_id,
                $request->payment_method_id
            );

            return response()->json([
                'success' => true,
                'status' => $intent->status,
                'payment_intent' => $intent,
            ]);

        } catch (\Exception $e) {
            Log::error('Payment confirmation failed', [
                'error' => $e->getMessage(),
                'payment_intent_id' => $request->payment_intent_id,
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get payment intent status
     * GET /api/payments/intent/{id}
     */
    public function getIntent($id)
    {
        try {
            $intent = $this->stripeService->getPaymentIntent($id);

            return response()->json([
                'success' => true,
                'payment_intent' => $intent,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Create a Terminal connection token for Stripe Terminal SDK
     * POST /api/payments/terminal/connection-token
     */
    public function createConnectionToken()
    {
        try {
            $token = $this->stripeService->createConnectionToken();

            return response()->json($token);

        } catch (\Exception $e) {
            Log::error('Connection token creation failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Create a payment link (optional fallback)
     * POST /api/payments/link
     */
    public function createPaymentLink(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string',
        ]);

        try {
            $link = $this->stripeService->createPaymentLink(
                $request->amount,
                $request->description ?? 'POS Order',
                route('pos.payment.complete')
            );

            return response()->json([
                'success' => true,
                'url' => $link->url,
                'qr_code_url' => 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($link->url),
            ]);

        } catch (\Exception $e) {
            Log::error('Payment Link creation failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Refund a payment
     * POST /api/payments/refund
     */
    public function refundPayment(Request $request)
    {
        $request->validate([
            'payment_intent_id' => 'required|string',
            'order_id' => 'nullable|integer|exists:orders,id',
            'amount' => 'nullable|numeric|min:0.01',
            'reason' => 'nullable|string',
        ]);

        try {
            // First, check if the payment intent has a successful charge
            $paymentIntent = $this->stripeService->getPaymentIntent($request->payment_intent_id);
            
            if (!$paymentIntent || $paymentIntent->status !== 'succeeded') {
                throw new \Exception('Payment was not successfully completed. Cannot refund uncaptured or failed payments. Payment status: ' . ($paymentIntent->status ?? 'unknown'));
            }
            
            $refund = $this->stripeService->refundPayment(
                $request->payment_intent_id,
                $request->amount
            );

            // If order_id provided, update order status and create refund bank transaction
            if ($request->order_id) {
                $order = \App\Models\Order::find($request->order_id);
                if ($order) {
                    // Update order status
                    $order->update([
                        'order_status' => 'refunded',
                        'notes' => ($order->notes ? $order->notes . "\n\n" : '') . 
                                   'REFUNDED: ' . ($request->reason ?: 'No reason provided') . 
                                   ' - ' . now()->toDateTimeString()
                    ]);
                    
                    // Create refund transaction in bank_transactions (negative income)
                    \App\Models\BankTransaction::create([
                        'transaction_date' => now(),
                        'description' => "POS Refund - {$order->order_number}" . 
                                       ($order->customer_name && $order->customer_name !== 'Walk-in Customer' ? " ({$order->customer_name})" : ''),
                        'amount' => -abs($order->total_amount), // Negative amount for refund
                        'type' => 'debit', // Outgoing money (refund)
                        'reference' => $order->order_number . '-REFUND',
                        'category' => 'pos_sales',
                        'notes' => 'Refund: ' . ($request->reason ?: 'Customer refund request'),
                        'matched_order_id' => $order->id,
                        'imported_at' => now(),
                        'imported_by' => \Session::get('user.id', 1),
                    ]);
                    
                    \Log::info('POS refund processed', [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'amount' => $order->total_amount,
                        'refund_id' => $refund['id'] ?? null,
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'refund' => $refund,
            ]);

        } catch (\Exception $e) {
            Log::error('Refund failed', [
                'error' => $e->getMessage(),
                'payment_intent_id' => $request->payment_intent_id,
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get Stripe publishable key
     * GET /api/payments/config
     */
    public function getConfig()
    {
        try {
            return response()->json([
                'publishable_key' => $this->stripeService->getPublishableKey(),
                'currency' => setting('pos_currency', 'gbp'),
                'reader_type' => setting('pos_card_reader_type', 'manual'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle Stripe webhooks
     * POST /webhooks/stripe
     */
    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');

        try {
            $event = $this->stripeService->verifyWebhook($payload, $signature);

            Log::info('Stripe webhook received', [
                'type' => $event->type,
                'id' => $event->id,
            ]);

            // Handle different event types
            switch ($event->type) {
                case 'payment_intent.succeeded':
                    $this->handlePaymentSucceeded($event->data->object);
                    break;

                case 'payment_intent.payment_failed':
                    $this->handlePaymentFailed($event->data->object);
                    break;

                case 'charge.refunded':
                    $this->handleRefund($event->data->object);
                    break;
                
                case 'payout.paid':
                    $this->handlePayoutPaid($event->data->object);
                    break;
                
                case 'payout.failed':
                    $this->handlePayoutFailed($event->data->object);
                    break;

                // Add more event handlers as needed
            }

            return response()->json(['received' => true]);

        } catch (\Exception $e) {
            Log::error('Webhook handling failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Webhook handling failed',
            ], 400);
        }
    }

    /**
     * Handle successful payment
     */
    protected function handlePaymentSucceeded($paymentIntent)
    {
        Log::info('Payment succeeded', [
            'payment_intent_id' => $paymentIntent->id,
            'amount' => $paymentIntent->amount / 100,
            'metadata' => $paymentIntent->metadata,
        ]);

        // Find order by payment intent ID
        $order = \App\Models\Order::where('stripe_payment_intent_id', $paymentIntent->id)->first();
        
        if ($order) {
            // Get the charge ID from the payment intent
            $chargeId = $paymentIntent->charges->data[0]->id ?? null;
            $customerId = $paymentIntent->customer ?? null;
            
            // Update order with charge details
            $order->update([
                'stripe_charge_id' => $chargeId,
                'stripe_customer_id' => $customerId,
                'payment_status' => 'paid',
                'stripe_metadata' => array_merge($order->stripe_metadata ?? [], [
                    'charge_id' => $chargeId,
                    'amount_received' => $paymentIntent->amount_received / 100,
                    'payment_method_types' => $paymentIntent->payment_method_types,
                    'status' => $paymentIntent->status,
                    'paid_at' => now()->toIso8601String()
                ])
            ]);
            
            Log::info('Order updated with Stripe charge', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'charge_id' => $chargeId
            ]);
        } else {
            Log::warning('No order found for payment intent', [
                'payment_intent_id' => $paymentIntent->id
            ]);
        }
    }

    /**
     * Handle failed payment
     */
    protected function handlePaymentFailed($paymentIntent)
    {
        Log::warning('Payment failed', [
            'payment_intent_id' => $paymentIntent->id,
            'error' => $paymentIntent->last_payment_error,
        ]);

        // Handle failed payment (notify staff, update order, etc.)
    }

    /**
     * Handle refund
     */
    protected function handleRefund($charge)
    {
        Log::info('Payment refunded', [
            'charge_id' => $charge->id,
            'amount' => $charge->amount_refunded / 100,
        ]);

        // Update order with refund information
    }
    
    /**
     * Handle payout paid - match with bank transaction
     */
    protected function handlePayoutPaid($payout)
    {
        Log::info('Payout paid', [
            'payout_id' => $payout->id,
            'amount' => $payout->amount / 100,
            'arrival_date' => $payout->arrival_date,
        ]);
        
        $amount = $payout->amount / 100;
        $arrivalDate = \Carbon\Carbon::createFromTimestamp($payout->arrival_date);
        
        // Look for matching bank transaction (search +/- 3 days)
        $bankTx = \App\Models\BankTransaction::where('type', 'credit')
            ->where('description', 'like', '%STRIPE%')
            ->whereBetween('transaction_date', [
                $arrivalDate->copy()->subDays(3),
                $arrivalDate->copy()->addDays(3)
            ])
            ->where('amount', $amount)
            ->whereNull('stripe_payout_id')
            ->first();
        
        if ($bankTx) {
            // Get charges for this payout
            $stripeService = app(\App\Services\StripeService::class);
            $charges = $stripeService->getChargesForPayout($payout->id);
            
            $bankTx->stripe_payout_id = $payout->id;
            $bankTx->stripe_charges = $charges->toJson();
            $bankTx->save();
            
            Log::info('Payout matched to bank transaction', [
                'payout_id' => $payout->id,
                'bank_transaction_id' => $bankTx->id,
                'charges_count' => $charges->count(),
            ]);
        } else {
            Log::warning('No matching bank transaction found for payout', [
                'payout_id' => $payout->id,
                'amount' => $amount,
                'arrival_date' => $arrivalDate->toDateString(),
            ]);
        }
    }
    
    /**
     * Handle failed payout
     */
    protected function handlePayoutFailed($payout)
    {
        Log::warning('Payout failed', [
            'payout_id' => $payout->id,
            'failure_message' => $payout->failure_message ?? 'Unknown',
        ]);
    }
}
