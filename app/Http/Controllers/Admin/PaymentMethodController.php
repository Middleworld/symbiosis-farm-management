<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserPaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;

class PaymentMethodController extends Controller
{
    protected StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret'));
    }

    /**
     * Show payment methods for a user
     */
    public function index(Request $request, $userId)
    {
        $user = User::findOrFail($userId);
        
        $paymentMethods = UserPaymentMethod::where('user_id', $userId)
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.payment-methods.index', compact('user', 'paymentMethods'));
    }

    /**
     * Create setup intent for adding new payment method
     */
    public function setupIntent(Request $request, $userId)
    {
        try {
            $user = User::findOrFail($userId);

            // Ensure user has Stripe customer ID
            if (!$user->stripe_customer_id) {
                $customer = $this->stripe->customers->create([
                    'email' => $user->email,
                    'name' => $user->name,
                    'metadata' => [
                        'user_id' => $user->id,
                    ],
                ]);

                $user->update(['stripe_customer_id' => $customer->id]);
            }

            // Create setup intent
            $setupIntent = $this->stripe->setupIntents->create([
                'customer' => $user->stripe_customer_id,
                'payment_method_types' => ['card'],
                'usage' => 'off_session',
            ]);

            return response()->json([
                'success' => true,
                'client_secret' => $setupIntent->client_secret,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to create setup intent', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to initialize payment method setup',
            ], 500);
        }
    }

    /**
     * Store new payment method
     */
    public function store(Request $request, $userId)
    {
        $request->validate([
            'payment_method_id' => 'required|string',
        ]);

        try {
            $user = User::findOrFail($userId);

            // Attach payment method to customer
            $this->stripe->paymentMethods->attach(
                $request->payment_method_id,
                ['customer' => $user->stripe_customer_id]
            );

            // Get payment method details
            $paymentMethod = $this->stripe->paymentMethods->retrieve($request->payment_method_id);

            // Check if this should be the default (first payment method or explicitly requested)
            $isDefault = $request->boolean('is_default', false) || 
                         !UserPaymentMethod::where('user_id', $userId)->exists();

            // If setting as default, unset other defaults
            if ($isDefault) {
                UserPaymentMethod::where('user_id', $userId)
                    ->update(['is_default' => false]);

                // Update Stripe customer default payment method
                $this->stripe->customers->update($user->stripe_customer_id, [
                    'invoice_settings' => [
                        'default_payment_method' => $request->payment_method_id,
                    ],
                ]);

                $user->update(['stripe_default_payment_method_id' => $request->payment_method_id]);
            }

            // Store in database
            $userPaymentMethod = UserPaymentMethod::create([
                'user_id' => $userId,
                'provider' => 'stripe',
                'provider_payment_method_id' => $request->payment_method_id,
                'type' => $paymentMethod->type,
                'card_brand' => $paymentMethod->card->brand ?? null,
                'card_last4' => $paymentMethod->card->last4 ?? null,
                'card_exp_month' => $paymentMethod->card->exp_month ?? null,
                'card_exp_year' => $paymentMethod->card->exp_year ?? null,
                'is_default' => $isDefault,
            ]);

            Log::info('Payment method added', [
                'user_id' => $userId,
                'payment_method_id' => $request->payment_method_id,
                'is_default' => $isDefault,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment method added successfully',
                'payment_method' => $userPaymentMethod,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to add payment method', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to add payment method: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Set payment method as default
     */
    public function setDefault(Request $request, $userId, $paymentMethodId)
    {
        try {
            $user = User::findOrFail($userId);
            $paymentMethod = UserPaymentMethod::where('user_id', $userId)
                ->where('id', $paymentMethodId)
                ->firstOrFail();

            // Unset other defaults
            UserPaymentMethod::where('user_id', $userId)
                ->update(['is_default' => false]);

            // Set this as default
            $paymentMethod->update(['is_default' => true]);

            // Update Stripe
            $this->stripe->customers->update($user->stripe_customer_id, [
                'invoice_settings' => [
                    'default_payment_method' => $paymentMethod->provider_payment_method_id,
                ],
            ]);

            // Update user record
            $user->update(['stripe_default_payment_method_id' => $paymentMethod->provider_payment_method_id]);

            Log::info('Default payment method updated', [
                'user_id' => $userId,
                'payment_method_id' => $paymentMethod->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Default payment method updated',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to set default payment method', [
                'user_id' => $userId,
                'payment_method_id' => $paymentMethodId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to update default payment method',
            ], 500);
        }
    }

    /**
     * Delete payment method
     */
    public function destroy(Request $request, $userId, $paymentMethodId)
    {
        try {
            $user = User::findOrFail($userId);
            $paymentMethod = UserPaymentMethod::where('user_id', $userId)
                ->where('id', $paymentMethodId)
                ->firstOrFail();

            // Don't allow deleting the last payment method if user has active subscriptions
            $hasActiveSubscriptions = $user->subscriptions()
                ->active()
                ->exists();

            $remainingMethods = UserPaymentMethod::where('user_id', $userId)
                ->where('id', '!=', $paymentMethodId)
                ->count();

            if ($hasActiveSubscriptions && $remainingMethods === 0) {
                return response()->json([
                    'success' => false,
                    'error' => 'Cannot delete the only payment method with active subscriptions',
                ], 400);
            }

            // Detach from Stripe
            $this->stripe->paymentMethods->detach($paymentMethod->provider_payment_method_id);

            // Delete from database
            $paymentMethod->delete();

            Log::info('Payment method deleted', [
                'user_id' => $userId,
                'payment_method_id' => $paymentMethodId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment method deleted',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to delete payment method', [
                'user_id' => $userId,
                'payment_method_id' => $paymentMethodId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to delete payment method',
            ], 500);
        }
    }
}
