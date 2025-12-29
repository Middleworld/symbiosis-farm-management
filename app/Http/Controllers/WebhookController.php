<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

class WebhookController extends Controller
{
    /**
     * Handle Stripe webhooks
     */
    public function handleStripe(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = config('services.stripe.webhook_secret');

        try {
            // Verify webhook signature
            if ($webhookSecret) {
                $event = Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
            } else {
                // For testing without signature verification
                $event = json_decode($payload, true);
            }

            Log::info('Stripe webhook received', [
                'type' => $event['type'] ?? 'unknown',
                'id' => $event['id'] ?? 'unknown'
            ]);

            // Handle the event
            switch ($event['type']) {
                case 'charge.refunded':
                    $this->handleChargeRefunded($event['data']['object']);
                    break;

                case 'payment_intent.canceled':
                    $this->handlePaymentIntentCanceled($event['data']['object']);
                    break;

                case 'payment_intent.payment_failed':
                    $this->handlePaymentIntentFailed($event['data']['object']);
                    break;

                case 'payment_intent.succeeded':
                    $this->handlePaymentIntentSucceeded($event['data']['object']);
                    break;

                case 'invoice.payment_succeeded':
                    $this->handleInvoicePaymentSucceeded($event['data']['object']);
                    break;

                case 'invoice.payment_failed':
                    $this->handleInvoicePaymentFailed($event['data']['object']);
                    break;

                default:
                    Log::info('Unhandled webhook event type', ['type' => $event['type']]);
            }

            return response()->json(['status' => 'success']);

        } catch (SignatureVerificationException $e) {
            Log::error('Stripe webhook signature verification failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid signature'], 400);
        } catch (\Exception $e) {
            Log::error('Stripe webhook processing failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    /**
     * Handle charge refunded event
     */
    protected function handleChargeRefunded($charge)
    {
        $paymentIntentId = $charge['payment_intent'] ?? null;

        if (!$paymentIntentId) {
            Log::warning('Charge refunded but no payment intent ID', ['charge_id' => $charge['id']]);
            return;
        }

        $order = Order::where('stripe_payment_intent_id', $paymentIntentId)->first();

        if (!$order) {
            Log::warning('Order not found for refunded charge', ['payment_intent' => $paymentIntentId]);
            return;
        }

        // Check if it's a partial or full refund
        $amountRefunded = $charge['amount_refunded'] ?? 0;
        $totalAmount = $charge['amount'] ?? 0;
        $isFullRefund = $amountRefunded >= $totalAmount;

        $order->payment_status = $isFullRefund ? 'refunded' : 'partially_refunded';
        $order->order_status = $isFullRefund ? 'refunded' : $order->order_status;
        
        $refundNote = "Webhook: " . ($isFullRefund ? 'Full' : 'Partial') . " refund processed via Stripe on " . 
                     now()->format('Y-m-d H:i:s') . ". " .
                     "Amount refunded: £" . number_format($amountRefunded / 100, 2);
        
        $order->notes = ($order->notes ? $order->notes . "\n\n" : '') . $refundNote;
        $order->save();

        Log::info('Order updated from refund webhook', [
            'order_id' => $order->id,
            'payment_intent' => $paymentIntentId,
            'amount_refunded' => $amountRefunded / 100
        ]);
    }

    /**
     * Handle payment intent canceled event
     */
    protected function handlePaymentIntentCanceled($paymentIntent)
    {
        $paymentIntentId = $paymentIntent['id'] ?? null;

        if (!$paymentIntentId) {
            return;
        }

        $order = Order::where('stripe_payment_intent_id', $paymentIntentId)->first();

        if (!$order) {
            return;
        }

        $order->payment_status = 'canceled';
        $order->order_status = 'cancelled';
        $order->notes = ($order->notes ? $order->notes . "\n\n" : '') . 
                       "Webhook: Payment canceled on " . now()->format('Y-m-d H:i:s');
        $order->save();

        Log::info('Order updated from payment canceled webhook', [
            'order_id' => $order->id,
            'payment_intent' => $paymentIntentId
        ]);
    }

    /**
     * Handle payment intent failed event
     */
    protected function handlePaymentIntentFailed($paymentIntent)
    {
        $paymentIntentId = $paymentIntent['id'] ?? null;

        if (!$paymentIntentId) {
            return;
        }

        $order = Order::where('stripe_payment_intent_id', $paymentIntentId)->first();

        if (!$order) {
            return;
        }

        $order->payment_status = 'failed';
        $order->notes = ($order->notes ? $order->notes . "\n\n" : '') . 
                       "Webhook: Payment failed on " . now()->format('Y-m-d H:i:s') .
                       ". Reason: " . ($paymentIntent['last_payment_error']['message'] ?? 'Unknown');
        $order->save();

        Log::info('Order updated from payment failed webhook', [
            'order_id' => $order->id,
            'payment_intent' => $paymentIntentId
        ]);
    }

    /**
     * Handle payment intent succeeded event
     */
    protected function handlePaymentIntentSucceeded($paymentIntent)
    {
        $paymentIntentId = $paymentIntent['id'] ?? null;

        if (!$paymentIntentId) {
            return;
        }

        $order = Order::where('stripe_payment_intent_id', $paymentIntentId)->first();

        if (!$order) {
            // This is normal - order might be created after payment
            return;
        }

        // Only update if payment status isn't already paid
        if ($order->payment_status !== 'paid') {
            $order->payment_status = 'paid';
            $order->order_status = 'completed';
            $order->completed_at = now();
            $order->notes = ($order->notes ? $order->notes . "\n\n" : '') . 
                           "Webhook: Payment confirmed on " . now()->format('Y-m-d H:i:s');
            $order->save();

            Log::info('Order updated from payment succeeded webhook', [
                'order_id' => $order->id,
                'payment_intent' => $paymentIntentId
            ]);
        }
    }

    /**
     * Handle invoice payment succeeded event
     */
    protected function handleInvoicePaymentSucceeded($invoice)
    {
        $customerId = $invoice['customer'] ?? null;
        $invoiceId = $invoice['id'] ?? null;
        $amountPaid = $invoice['amount_paid'] ?? 0;

        if (!$customerId) {
            Log::warning('Invoice paid but no customer ID', ['invoice_id' => $invoiceId]);
            return;
        }

        // Find subscription by Stripe customer ID
        $subscription = \App\Models\VegboxSubscription::where('stripe_customer_id', $customerId)
            ->whereNull('canceled_at')
            ->first();

        if (!$subscription) {
            Log::warning('Subscription not found for invoice payment', [
                'customer_id' => $customerId,
                'invoice_id' => $invoiceId
            ]);
            return;
        }

        // Clear failed payment tracking and mark as paid
        $subscription->failed_payment_count = 0;
        $subscription->last_payment_error = null;
        $subscription->last_payment_attempt_at = now();
        $subscription->next_retry_at = null;
        $subscription->grace_period_ends_at = null;
        $subscription->save();

        Log::info('Subscription updated from invoice payment webhook', [
            'subscription_id' => $subscription->id,
            'customer_id' => $customerId,
            'invoice_id' => $invoiceId,
            'amount' => $amountPaid / 100
        ]);
    }

    /**
     * Handle invoice payment failed event - CRITICAL FOR ALERTING ON FAILED PAYMENTS
     */
    protected function handleInvoicePaymentFailed($invoice)
    {
        $customerId = $invoice['customer'] ?? null;
        $invoiceId = $invoice['id'] ?? null;
        $attemptCount = $invoice['attempt_count'] ?? 0;
        $nextPaymentAttempt = $invoice['next_payment_attempt'] ?? null;
        $errorMessage = $invoice['last_finalization_error']['message'] ?? 
                       ($invoice['charge']['failure_message'] ?? 'Payment failed');

        if (!$customerId) {
            Log::warning('Invoice payment failed but no customer ID', ['invoice_id' => $invoiceId]);
            return;
        }

        // Find subscription by Stripe customer ID
        $subscription = \App\Models\VegboxSubscription::where('stripe_customer_id', $customerId)
            ->whereNull('canceled_at')
            ->first();

        if (!$subscription) {
            Log::error('PAYMENT FAILED - Subscription not found in database', [
                'customer_id' => $customerId,
                'invoice_id' => $invoiceId,
                'error' => $errorMessage,
                'attempt' => $attemptCount
            ]);
            return;
        }

        // Record the failed payment
        $subscription->recordFailedPayment($errorMessage);

        Log::error('PAYMENT FAILED - Subscription payment failed', [
            'subscription_id' => $subscription->id,
            'customer_id' => $customerId,
            'customer_email' => $subscription->user ? $subscription->user->email : 'unknown',
            'invoice_id' => $invoiceId,
            'attempt' => $attemptCount,
            'failed_count' => $subscription->failed_payment_count,
            'error' => $errorMessage,
            'next_attempt' => $nextPaymentAttempt ? date('Y-m-d H:i:s', $nextPaymentAttempt) : 'none',
            'status_updated_to' => $subscription->fresh()->canceled_at ? 'CANCELLED' : 'ACTIVE'
        ]);

        // Send alert notification if this is a critical failure
        if ($subscription->failed_payment_count >= 3) {
            Log::critical('URGENT: Subscription has 3+ failed payments', [
                'subscription_id' => $subscription->id,
                'customer_email' => $subscription->user ? $subscription->user->email : 'unknown',
                'amount' => $subscription->price,
                'failed_count' => $subscription->failed_payment_count
            ]);
        }
    }
}
