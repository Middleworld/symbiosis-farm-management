<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VegboxSubscription;
use App\Models\VegboxPlan;
use App\Models\User;
use App\Services\VegboxPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class VegboxSubscriptionApiController extends Controller
{
    protected VegboxPaymentService $paymentService;

    public function __construct(VegboxPaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Get all subscriptions for a WordPress user
     * 
     * @param Request $request
     * @param int $user_id WordPress user ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserSubscriptions(Request $request, $user_id)
    {
        try {
            // Query subscriptions directly by wordpress_user_id
            // No need to map to Laravel users - WordPress is source of truth
            $subscriptions = VegboxSubscription::with(['plan'])
                ->where('wordpress_user_id', $user_id)
                // Status is calculated from canceled_at, ends_at, pause_until fields
                // Filter client-side after mapping
                ->get()
                ->map(function ($sub) {
                    // Determine status
                    if ($sub->isPaused()) {
                        $status = 'paused';
                    } elseif ($sub->canceled_at) {
                        $status = 'cancelled';
                    } elseif ($sub->ends_at && $sub->ends_at->isPast()) {
                        $status = 'expired';
                    } else {
                        $status = 'active';
                    }
                    
                    return [
                        'id' => (int) $sub->id,
                        'product_name' => $sub->plan->name ?? 'Vegbox Subscription',
                        'variation_name' => '', // TODO: Add variation tracking
                        'status' => $status,
                        'billing_amount' => (float) $sub->price,
                        'billing_period' => $sub->billing_period ?? 'month',
                        'delivery_day' => $sub->delivery_day ?? '',
                        'next_billing_date' => $sub->next_billing_at ? $sub->next_billing_at->format('Y-m-d') : '',
                        'created_at' => $sub->created_at->format('Y-m-d'),
                        'manage_url' => url('/admin/vegbox-subscriptions/' . $sub->id),
                    ];
                })
                ->filter(function ($sub) {
                    // Only show active and paused subscriptions to customers
                    return in_array($sub['status'], ['active', 'paused']);
                })
                ->values(); // Re-index array after filtering

            Log::info('API: Retrieved user subscriptions', [
                'wordpress_user_id' => $user_id,
                'subscription_count' => $subscriptions->count()
            ]);

            return response()->json([
                'success' => true,
                'subscriptions' => $subscriptions
            ]);

        } catch (\Exception $e) {
            Log::error('API: Get user subscriptions failed', [
                'user_id' => $user_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve subscriptions'
            ], 500);
        }
    }

    /**
     * Create subscription after WooCommerce order completes
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createSubscription(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'wordpress_user_id' => 'required|integer',
            'wordpress_order_id' => 'required|integer',
            'product_id' => 'required|integer', // WooCommerce product ID
            'variation_id' => 'nullable|integer', // WooCommerce variation ID
            'billing_period' => 'required|string|in:week,month',
            'billing_interval' => 'required|integer|min:1',
            'billing_amount' => 'required|numeric|min:0',
            'delivery_day' => 'required|string|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'payment_method' => 'nullable|string',
            'payment_method_token' => 'nullable|string',
            'customer_email' => 'required|email',
            'billing_address' => 'nullable|array', // Optional - WooCommerce shipping address
        ]);

        if ($validator->fails()) {
            Log::warning('API: Create subscription validation failed', [
                'errors' => $validator->errors()->toArray(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Store WordPress user ID directly (WordPress is source of truth for users)
            // No need to validate - WordPress only sends valid user IDs from logged-in customers
            $wordpress_user_id = $request->wordpress_user_id;

            // Check if subscription already exists for this order
            $existingSubscription = VegboxSubscription::where('woo_subscription_id', $request->wordpress_order_id)->first();
            
            if ($existingSubscription) {
                Log::info('API: Subscription already exists for order', [
                    'wordpress_order_id' => $request->wordpress_order_id,
                    'subscription_id' => $existingSubscription->id
                ]);

                return response()->json([
                    'success' => true,
                    'subscription_id' => (int) $existingSubscription->id,
                    'status' => 'active',
                    'next_billing_date' => $existingSubscription->next_billing_at ? $existingSubscription->next_billing_at->format('Y-m-d') : now()->addMonth()->format('Y-m-d'),
                    'message' => 'Subscription already exists'
                ], 200);
            }

            // Map WooCommerce product_id to plan_id
            // For now, use a simple mapping or create plan on the fly
            // TODO: Implement proper product_id → plan_id mapping
            $plan = \App\Models\VegboxPlan::first(); // Temporary: get first plan
            if (!$plan) {
                // Create a default plan if none exist
                $plan = \App\Models\VegboxPlan::create([
                    'name' => ['en' => 'Vegbox Subscription'],
                    'price' => $request->billing_amount,
                    'currency' => 'GBP',
                    'billing_frequency' => $request->billing_interval,
                    'billing_period' => $request->billing_period,
                ]);
            }

            $subscription = VegboxSubscription::create([
                'wordpress_user_id' => $wordpress_user_id,
                'subscriber_id' => null, // Not needed - WordPress users don't exist in Laravel database
                'subscriber_type' => null,
                'plan_id' => $plan->id,
                'name' => ['en' => $request->variation_name ?? $request->product_name ?? $plan->name ?? 'Vegbox Subscription'],
                'price' => $request->billing_amount,
                'billing_frequency' => $request->billing_interval,
                'billing_period' => $request->billing_period,
                'starts_at' => now(),
                'ends_at' => null, // Don't expire - WordPress will manage subscription lifecycle
                'next_billing_at' => $this->calculateNextBilling($request->billing_period, $request->billing_interval),
                'next_delivery_date' => $this->calculateNextDelivery($request->delivery_day),
                'delivery_day' => $request->delivery_day,
                'woo_subscription_id' => $request->wordpress_order_id,
                'wc_order_id' => $request->wc_order_id ?? $request->wordpress_order_id, // WooCommerce order ID
                // 'woocommerce_product_id' => $request->product_id, // TODO: Add this field to migration
                'imported_from_woo' => false,
            ]);

            // Clear ends_at if it was set by parent package
            // WordPress manages subscription lifecycle, not Laravel
            if ($subscription->ends_at) {
                $subscription->ends_at = null;
                $subscription->saveQuietly(); // Save without triggering events
            }

            // TODO: Store delivery address in separate table if provided
            // $request->billing_address is optional

            Log::info('API: Subscription created successfully', [
                'subscription_id' => $subscription->id,
                'wordpress_user_id' => $wordpress_user_id,
                'wordpress_order_id' => $request->wordpress_order_id,
                'product_id' => $request->product_id,
                'delivery_day' => $request->delivery_day
            ]);

            return response()->json([
                'success' => true,
                'subscription_id' => (int) $subscription->id,
                'status' => 'active',
                'next_billing_date' => $subscription->next_billing_at->format('Y-m-d'),
                'message' => 'Subscription created successfully'
            ], 201);

        } catch (\Exception $e) {
            Log::error('API: Create subscription failed', [
                'wordpress_user_id' => $request->wordpress_user_id ?? null,
                'wordpress_order_id' => $request->wordpress_order_id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create subscription: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle subscription actions (pause/resume/cancel)
     * Using single endpoint to avoid ModSecurity blocking /resume and /cancel
     * 
     * @param Request $request
     * @param int $id Subscription ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function handleSubscriptionAction(Request $request, $id)
    {
        $action = $request->input('action');
        
        // Validate action
        if (!in_array($action, ['pause', 'resume', 'cancel', 'change_plan', 'change_delivery_method', 'change_frequency'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid action. Must be pause, resume, cancel, change_plan, change_delivery_method, or change_frequency.'
            ], 400);
        }
        
        try {
            $subscription = VegboxSubscription::findOrFail($id);
            
            switch ($action) {
                case 'pause':
                    return $this->handlePause($request, $subscription);
                    
                case 'resume':
                    return $this->handleResume($subscription);
                    
                case 'cancel':
                    return $this->handleCancel($subscription);
                
                case 'change_plan':
                    return $this->handleChangePlan($request, $subscription);
                
                case 'change_delivery_method':
                    return $this->handleChangeDeliveryMethod($request, $subscription);
                
                case 'change_frequency':
                    return $this->handleChangeFrequency($request, $subscription);
            }
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Subscription not found'
            ], 404);
            
        } catch (\Exception $e) {
            Log::error('API: Subscription action failed', [
                'subscription_id' => $id,
                'action' => $action,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to process action'
            ], 500);
        }
    }

    /**
     * Handle pause action
     * 
     * @param Request $request
     * @param VegboxSubscription $subscription
     * @return \Illuminate\Http\JsonResponse
     */
    private function handlePause(Request $request, VegboxSubscription $subscription)
    {
        $pauseUntil = $request->input('pause_until');
        
        if (!$pauseUntil) {
            return response()->json([
                'success' => false,
                'message' => 'pause_until date is required'
            ], 400);
        }
        
        try {
            $pauseDate = Carbon::parse($pauseUntil);
            
            if ($pauseDate->isPast()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pause date must be in the future'
                ], 400);
            }
            
            $subscription->pauseUntil($pauseDate);
            
            Log::info('API: Subscription paused', [
                'subscription_id' => $subscription->id,
                'pause_until' => $pauseUntil
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Subscription paused successfully',
                'pause_until' => $pauseDate->format('Y-m-d'),
                'next_delivery_date' => $subscription->next_delivery_date?->format('Y-m-d')
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid pause_until date format'
            ], 400);
        }
    }
    
    /**
     * Handle resume action
     * 
     * @param VegboxSubscription $subscription
     * @return \Illuminate\Http\JsonResponse
     */
    private function handleResume(VegboxSubscription $subscription)
    {
        if (!$subscription->isPaused()) {
            return response()->json([
                'success' => false,
                'message' => 'Subscription is not paused'
            ], 400);
        }
        
        $subscription->resume();
        
        Log::info('API: Subscription resumed', [
            'subscription_id' => $subscription->id
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Subscription resumed successfully',
            'next_delivery_date' => $subscription->next_delivery_date?->format('Y-m-d')
        ]);
    }
    
    /**
     * Handle cancel action
     * 
     * @param VegboxSubscription $subscription
     * @return \Illuminate\Http\JsonResponse
     */
    private function handleCancel(VegboxSubscription $subscription)
    {
        if ($subscription->canceled_at) {
            return response()->json([
                'success' => false,
                'message' => 'Subscription is already cancelled'
            ], 400);
        }
        
        $endsAt = $subscription->next_billing_at ?? now()->addMonth();
        
        $subscription->update([
            'canceled_at' => now(),
            'ends_at' => $endsAt
        ]);
        
        Log::info('API: Subscription cancelled', [
            'subscription_id' => $subscription->id,
            'ends_at' => $endsAt->format('Y-m-d')
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Subscription will be cancelled at the end of the current billing period',
            'ends_at' => $endsAt->format('Y-m-d')
        ]);
    }

    /**
     * Handle plan change action (upgrade/downgrade)
     * 
     * @param Request $request
     * @param VegboxSubscription $subscription
     * @return \Illuminate\Http\JsonResponse
     */
    private function handleChangePlan(Request $request, VegboxSubscription $subscription)
    {
        // Validate new plan ID
        $validator = Validator::make($request->all(), [
            'new_plan_id' => 'required|integer|exists:vegbox_plans,id'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid plan ID',
                'errors' => $validator->errors()
            ], 400);
        }
        
        $newPlanId = $request->input('new_plan_id');
        
        // Check if it's actually a different plan
        if ($subscription->plan_id == $newPlanId) {
            return response()->json([
                'success' => false,
                'message' => 'Subscription is already on this plan'
            ], 400);
        }
        
        // Get the new plan details
        $newPlan = VegboxPlan::find($newPlanId);
        
        if (!$newPlan) {
            return response()->json([
                'success' => false,
                'message' => 'Plan not found'
            ], 404);
        }
        
        // Store the current plan for logging
        $oldPlanId = $subscription->plan_id;
        $oldAmount = $subscription->billing_amount;
        
        // Update subscription with new plan
        // Plan change takes effect at next renewal (customer-friendly approach)
        $subscription->update([
            'plan_id' => $newPlanId,
            'billing_amount' => $newPlan->price,
            'product_id' => $newPlan->product_id,
            'product_name' => $newPlan->name,
            'variation_id' => $newPlan->variation_id ?? null,
            'variation_name' => $newPlan->variation_name ?? null
        ]);
        
        Log::info('API: Subscription plan changed', [
            'subscription_id' => $subscription->id,
            'old_plan_id' => $oldPlanId,
            'new_plan_id' => $newPlanId,
            'old_amount' => $oldAmount,
            'new_amount' => $newPlan->price,
            'next_billing_at' => $subscription->next_billing_at?->format('Y-m-d')
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Plan changed successfully. New plan will take effect at next billing cycle.',
            'new_plan' => [
                'id' => $newPlan->id,
                'name' => $newPlan->name,
                'price' => $newPlan->price
            ],
            'next_billing_at' => $subscription->next_billing_at?->format('Y-m-d'),
            'next_billing_amount' => $newPlan->price
        ]);
    }

    /**
     * Handle delivery method change action (delivery <-> collection)
     * 
     * @param Request $request
     * @param VegboxSubscription $subscription
     * @return \Illuminate\Http\JsonResponse
     */
    private function handleChangeDeliveryMethod(Request $request, VegboxSubscription $subscription)
    {
        $validator = Validator::make($request->all(), [
            'delivery_method' => 'required|in:delivery,collection'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid delivery method',
                'errors' => $validator->errors()
            ], 422);
        }

        $newDeliveryMethod = $request->input('delivery_method');
        
        // If already on this delivery method, return success
        if ($subscription->delivery_method === $newDeliveryMethod) {
            return response()->json([
                'success' => true,
                'message' => 'Subscription is already using this delivery method',
                'subscription' => [
                    'id' => $subscription->id,
                    'delivery_method' => $subscription->delivery_method,
                    'price' => $subscription->price
                ]
            ]);
        }
        
        // Extract box size from subscription name
        $subscriptionName = is_array($subscription->name) ? ($subscription->name['en'] ?? '') : json_decode($subscription->name, true)['en'] ?? '';
        
        // Determine box size from name (Single Person, Couple's, Small Family, Large Family)
        $boxSize = null;
        if (stripos($subscriptionName, "Couple") !== false) {
            $boxSize = "Couple";
        } elseif (stripos($subscriptionName, "Small Family") !== false) {
            $boxSize = "Small Family";
        } elseif (stripos($subscriptionName, "Large Family") !== false) {
            $boxSize = "Large Family";
        } elseif (stripos($subscriptionName, "Single") !== false) {
            $boxSize = "Single";
        }
        
        if (!$boxSize) {
            return response()->json([
                'success' => false,
                'message' => 'Could not determine box size from subscription name'
            ], 400);
        }
        
        // Get current subscription attributes
        $currentBillingPeriod = $subscription->billing_period; // 'week', 'month', 'year'
        $currentBillingFrequency = $subscription->billing_frequency ?? 1;
        
        // Find the appropriate plan based on box size + billing + new delivery method
        $newPlan = VegboxPlan::where(function($query) use ($boxSize) {
                $query->where('name', 'like', '%' . $boxSize . '%')
                      ->orWhere('slug', 'like', '%' . strtolower(str_replace(' ', '-', $boxSize)) . '%');
            })
            ->where('invoice_interval', $currentBillingPeriod)
            ->where('invoice_period', $currentBillingFrequency)
            ->where(function($query) use ($newDeliveryMethod) {
                // Match delivery method in slug or name
                if ($newDeliveryMethod === 'delivery') {
                    // For delivery, look for plans with 'delivery' keyword
                    $query->where(function($q) {
                        $q->where('slug', 'like', '%delivery%')
                          ->orWhere('name', 'like', '%delivery%');
                    });
                } else {
                    // For collection, must explicitly have 'collection' keyword
                    $query->where('slug', 'like', '%collection%')
                          ->orWhere('name', 'like', '%collection%');
                }
            })
            ->first();

        if (!$newPlan) {
            Log::warning('No plan found for delivery method change', [
                'subscription_id' => $subscription->id,
                'box_size' => $boxSize,
                'subscription_name' => $subscriptionName,
                'billing_period' => $currentBillingPeriod,
                'billing_frequency' => $currentBillingFrequency,
                'requested_delivery_method' => $newDeliveryMethod
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'No plan found for the requested delivery method with your current box size and billing frequency'
            ], 404);
        }

        // Store old values for logging
        $oldDeliveryMethod = $subscription->delivery_method;
        $oldPrice = $subscription->price;

        // Update subscription
        $subscription->plan_id = $newPlan->id;
        $subscription->price = $newPlan->price;
        $subscription->delivery_method = $newDeliveryMethod;
        $subscription->save();

        // Sync to WooCommerce if needed
        if ($subscription->woo_subscription_id) {
            $this->syncToWooCommerce($subscription);
        }

        Log::info('Delivery method changed via action endpoint', [
            'subscription_id' => $subscription->id,
            'old_delivery_method' => $oldDeliveryMethod,
            'new_delivery_method' => $newDeliveryMethod,
            'old_price' => $oldPrice,
            'new_price' => $newPlan->price,
            'new_plan_id' => $newPlan->id
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Delivery method updated successfully',
            'subscription' => [
                'id' => $subscription->id,
                'delivery_method' => $subscription->delivery_method,
                'price' => $subscription->price,
                'plan_name' => $newPlan->name
            ]
        ]);
    }

    /**
     * Handle billing frequency change action
     * 
     * @param Request $request
     * @param VegboxSubscription $subscription
     * @return \Illuminate\Http\JsonResponse
     */
    private function handleChangeFrequency(Request $request, VegboxSubscription $subscription)
    {
        $validator = Validator::make($request->all(), [
            'billing_frequency' => 'required|in:weekly,fortnightly,monthly,annual',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid billing frequency',
                'errors' => $validator->errors()
            ], 422);
        }

        $billingFrequency = $request->input('billing_frequency'); // 'weekly', 'fortnightly', 'monthly', 'annual'
        
        // Map frequency to period and interval
        $periodMapping = [
            'weekly' => ['period' => 'week', 'interval' => 1],
            'fortnightly' => ['period' => 'week', 'interval' => 2],
            'monthly' => ['period' => 'month', 'interval' => 1],
            'annual' => ['period' => 'year', 'interval' => 1],
        ];
        
        $newPeriod = $periodMapping[$billingFrequency]['period'];
        $newInterval = $periodMapping[$billingFrequency]['interval'];
        
        // Check if already on this frequency
        if ($subscription->billing_period === $newPeriod && $subscription->billing_frequency == $newInterval) {
            return response()->json([
                'success' => true,
                'message' => 'Subscription is already using this billing frequency',
                'subscription' => [
                    'id' => $subscription->id,
                    'billing_period' => $subscription->billing_period,
                    'billing_frequency' => $subscription->billing_frequency,
                    'price' => $subscription->price
                ]
            ]);
        }
        
        // Extract box size from subscription name
        $subscriptionName = is_array($subscription->name) ? ($subscription->name['en'] ?? '') : json_decode($subscription->name, true)['en'] ?? '';
        
        // Determine box size from name (Single Person, Couple's, Small Family, Large Family)
        $boxSize = null;
        if (stripos($subscriptionName, "Couple") !== false) {
            $boxSize = "Couple";
        } elseif (stripos($subscriptionName, "Small Family") !== false) {
            $boxSize = "Small Family";
        } elseif (stripos($subscriptionName, "Large Family") !== false) {
            $boxSize = "Large Family";
        } elseif (stripos($subscriptionName, "Single") !== false) {
            $boxSize = "Single";
        }
        
        if (!$boxSize) {
            return response()->json([
                'success' => false,
                'message' => 'Could not determine box size from subscription name'
            ], 400);
        }
        
        // Get current subscription attributes
        $deliveryMethod = $subscription->delivery_method ?? 'delivery';
        
        // Find the appropriate plan by matching box size + billing frequency + delivery method
        $newPlan = VegboxPlan::where(function($query) use ($boxSize) {
                $query->where('name', 'like', '%' . $boxSize . '%')
                      ->orWhere('slug', 'like', '%' . strtolower(str_replace(' ', '-', $boxSize)) . '%');
            })
            ->where('invoice_interval', $newPeriod)
            ->where('invoice_period', $newInterval)
            ->where(function($query) use ($deliveryMethod) {
                // Match delivery method
                if ($deliveryMethod === 'delivery') {
                    $query->where(function($q) {
                        $q->where('slug', 'like', '%delivery%')
                          ->orWhere('name', 'like', '%delivery%');
                    });
                } else {
                    $query->where('slug', 'like', '%collection%')
                          ->orWhere('name', 'like', '%collection%');
                }
            })
            ->first();

        if (!$newPlan) {
            Log::warning('No plan found for frequency change', [
                'subscription_id' => $subscription->id,
                'box_size' => $boxSize,
                'subscription_name' => $subscriptionName,
                'requested_frequency' => $billingFrequency,
                'requested_period' => $newPeriod,
                'requested_interval' => $newInterval,
                'delivery_method' => $deliveryMethod
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'No plan found for the requested billing frequency with your current box size and delivery method'
            ], 404);
        }

        // Calculate new next billing date based on new frequency
        $now = Carbon::now();
        $nextBillingAt = match($billingFrequency) {
            'weekly' => $now->addWeek(),
            'fortnightly' => $now->addWeeks(2),
            'monthly' => $now->addMonth(),
            'annual' => $now->addYear(),
            default => $now->addWeek()
        };

        // Store old values for logging
        $oldBillingPeriod = $subscription->billing_period;
        $oldBillingFrequency = $subscription->billing_frequency;
        $oldPrice = $subscription->price;

        // Update subscription
        $subscription->plan_id = $newPlan->id;
        $subscription->price = $newPlan->price;
        $subscription->billing_period = $newPeriod;
        $subscription->billing_frequency = $newInterval;
        $subscription->next_billing_at = $nextBillingAt;
        $subscription->save();

        // Sync to WooCommerce if needed
        if ($subscription->woo_subscription_id) {
            $this->syncToWooCommerce($subscription);
        }

        Log::info('Billing frequency changed via action endpoint', [
            'subscription_id' => $subscription->id,
            'old_period' => $oldBillingPeriod,
            'old_frequency' => $oldBillingFrequency,
            'new_period' => $newPeriod,
            'new_frequency' => $newInterval,
            'old_price' => $oldPrice,
            'new_price' => $newPlan->price,
            'new_plan_id' => $newPlan->id,
            'next_billing_at' => $nextBillingAt->format('Y-m-d')
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Billing frequency updated successfully',
            'subscription' => [
                'id' => $subscription->id,
                'billing_period' => $subscription->billing_period,
                'billing_frequency' => $subscription->billing_frequency,
                'price' => $subscription->price,
                'next_billing_at' => $subscription->next_billing_at->format('Y-m-d'),
                'plan_name' => $newPlan->name
            ]
        ]);
    }

    /**
     * Pause subscription until a specific date
     * 
     * @param Request $request
     * @param int $id Subscription ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function pauseSubscription(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'pause_until' => 'required|date|after:today'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $subscription = VegboxSubscription::findOrFail($id);
            
            $pauseUntilDate = Carbon::parse($request->pause_until);
            $subscription->pauseUntil($pauseUntilDate);

            Log::info('API: Subscription paused', [
                'subscription_id' => $id,
                'pause_until' => $pauseUntilDate->format('Y-m-d')
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Subscription paused successfully',
                'pause_until' => $subscription->pause_until->format('Y-m-d'),
                'next_delivery_date' => $subscription->next_delivery_date?->format('Y-m-d')
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Subscription not found'
            ], 404);

        } catch (\Exception $e) {
            Log::error('API: Pause subscription failed', [
                'subscription_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to pause subscription'
            ], 500);
        }
    }

    /**
     * Resume a paused subscription
     * 
     * @param int $id Subscription ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function resumeSubscription($id)
    {
        try {
            $subscription = VegboxSubscription::findOrFail($id);
            $subscription->resume();

            Log::info('API: Subscription resumed', [
                'subscription_id' => $id,
                'next_delivery_date' => $subscription->next_delivery_date?->format('Y-m-d')
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Subscription resumed successfully',
                'next_delivery_date' => $subscription->next_delivery_date?->format('Y-m-d')
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Subscription not found'
            ], 404);

        } catch (\Exception $e) {
            Log::error('API: Resume subscription failed', [
                'subscription_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to resume subscription'
            ], 500);
        }
    }

    /**
     * Cancel subscription (sets canceled_at and ends_at dates)
     * 
     * @param int $id Subscription ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancelSubscription($id)
    {
        try {
            $subscription = VegboxSubscription::findOrFail($id);
            
            // Cancel at end of current billing period (grace period)
            $endsAt = $subscription->next_billing_at ?? now()->addMonth();
            
            $subscription->update([
                'canceled_at' => now(),
                'ends_at' => $endsAt
            ]);

            Log::info('API: Subscription cancelled', [
                'subscription_id' => $id,
                'canceled_at' => now()->format('Y-m-d'),
                'ends_at' => $endsAt->format('Y-m-d')
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Subscription will be cancelled at the end of the current billing period',
                'ends_at' => $endsAt->format('Y-m-d')
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Subscription not found'
            ], 404);

        } catch (\Exception $e) {
            Log::error('API: Cancel subscription failed', [
                'subscription_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel subscription'
            ], 500);
        }
    }

    /**
     * Update subscription delivery address
     * 
     * @param Request $request
     * @param int $id Subscription ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateAddress(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'delivery_address' => 'required|array',
            'delivery_address.address_1' => 'required|string',
            'delivery_address.city' => 'required|string',
            'delivery_address.postcode' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $subscription = VegboxSubscription::findOrFail($id);
            
            // TODO: Update delivery address in separate table
            // For now, just acknowledge the request
            
            Log::info('API: Subscription address update requested', [
                'subscription_id' => $id,
                'address' => $request->delivery_address
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Address update functionality coming soon'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Subscription not found'
            ], 404);

        } catch (\Exception $e) {
            Log::error('API: Update address failed', [
                'subscription_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update address'
            ], 500);
        }
    }

    /**
     * Get single subscription details
     * 
     * @param int $id Subscription ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSubscription($id)
    {
        try {
            $subscription = VegboxSubscription::with(['plan'])->findOrFail($id);
            
            // Determine status
            if ($subscription->isPaused()) {
                $status = 'paused';
            } elseif ($subscription->canceled_at) {
                $status = 'cancelled';
            } elseif ($subscription->ends_at && $subscription->ends_at->isPast()) {
                $status = 'expired';
            } else {
                $status = 'active';
            }
            
            // Get WordPress user ID directly from subscription
            $wpUserId = $subscription->wordpress_user_id ?? 0;
            
            // TODO: Get renewal orders from subscription_orders table when it exists
            $renewalOrders = [];

            return response()->json([
                'success' => true,
                'subscription' => [
                    'id' => (int) $subscription->id,
                    'user_id' => (int) $wpUserId,
                    'status' => $status,
                    'product_name' => $subscription->plan->name ?? 'Vegbox Subscription',
                    'variation_name' => '', // TODO: Add variation tracking
                    'billing_amount' => (float) $subscription->price,
                    'billing_period' => $subscription->billing_period ?? 'month',
                    'billing_interval' => (int) ($subscription->billing_frequency ?? 1),
                    'delivery_day' => $subscription->delivery_day ?? '',
                    'next_billing_date' => $subscription->next_billing_at ? $subscription->next_billing_at->format('Y-m-d') : '',
                    'last_billing_date' => $subscription->last_billing_date ? $subscription->last_billing_date->format('Y-m-d') : null,
                    'created_at' => $subscription->created_at->format('Y-m-d'),
                    'manage_url' => url('/admin/vegbox-subscriptions/' . $subscription->id),
                    'renewal_orders' => $renewalOrders,
                ]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Subscription not found'
            ], 404);

        } catch (\Exception $e) {
            Log::error('API: Get subscription failed', [
                'subscription_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve subscription'
            ], 500);
        }
    }

    /**
     * Get payment history for a subscription
     * 
     * @param int $id Subscription ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPayments($id)
    {
        try {
            $subscription = VegboxSubscription::findOrFail($id);
            
            // TODO: Get actual payment history from payment transactions table
            // For now, return placeholder
            
            return response()->json([
                'success' => true,
                'payments' => []
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Subscription not found'
            ], 404);

        } catch (\Exception $e) {
            Log::error('API: Get payments failed', [
                'subscription_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve payments'
            ], 500);
        }
    }

    /**
     * Map WordPress user ID to Laravel user
     * 
     * @param int $wpUserId
     * @return User|null
     */
    protected function mapWpUserToLaravel($wpUserId)
    {
        try {
            // Get WP user email from WordPress database
            $wpUser = \DB::connection('wordpress')
                ->table('users')
                ->where('ID', $wpUserId)
                ->first();

            if (!$wpUser) {
                Log::warning('API: WordPress user not found', ['wp_user_id' => $wpUserId]);
                return null;
            }

            // Find Laravel user by email or woo_customer_id
            $laravelUser = User::where('email', $wpUser->user_email)
                ->orWhere('woo_customer_id', $wpUserId)
                ->first();

            return $laravelUser;

        } catch (\Exception $e) {
            Log::error('API: Failed to map WP user to Laravel', [
                'wp_user_id' => $wpUserId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get or create Laravel user from WordPress user ID
     * 
     * @param int $wpUserId
     * @return User
     */
    protected function getOrCreateLaravelUser($wpUserId)
    {
        try {
            // Get WP user from WordPress database
            $wpUser = \DB::connection('wordpress')
                ->table('users')
                ->where('ID', $wpUserId)
                ->first();

            if (!$wpUser) {
                throw new \Exception("WordPress user {$wpUserId} not found");
            }

            // Find or create Laravel user
            $laravelUser = User::firstOrCreate(
                ['email' => $wpUser->user_email],
                [
                    'name' => $wpUser->display_name,
                    'woo_customer_id' => $wpUserId,
                    'password' => bcrypt(bin2hex(random_bytes(16))), // Random password
                ]
            );

            // Update woo_customer_id if it wasn't set
            if (!$laravelUser->woo_customer_id) {
                $laravelUser->update(['woo_customer_id' => $wpUserId]);
            }

            Log::info('API: Laravel user mapped/created', [
                'wp_user_id' => $wpUserId,
                'laravel_user_id' => $laravelUser->id,
                'email' => $laravelUser->email
            ]);

            return $laravelUser;

        } catch (\Exception $e) {
            Log::error('API: Failed to get/create Laravel user', [
                'wp_user_id' => $wpUserId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Calculate next delivery date based on day of week
     * 
     * @param string $dayOfWeek (monday, tuesday, etc.)
     * @return Carbon
     */
    protected function calculateNextDelivery($dayOfWeek)
    {
        $daysMap = [
            'monday' => 1,
            'tuesday' => 2,
            'wednesday' => 3,
            'thursday' => 4,
            'friday' => 5,
            'saturday' => 6,
            'sunday' => 7
        ];

        $targetDay = $daysMap[strtolower($dayOfWeek)] ?? 1;
        $today = now();
        $currentDay = $today->dayOfWeek ?: 7; // Convert Sunday from 0 to 7
        
        $daysUntilTarget = ($targetDay - $currentDay + 7) % 7;
        
        if ($daysUntilTarget === 0) {
            $daysUntilTarget = 7; // Next week if today is the delivery day
        }

        return $today->copy()->addDays($daysUntilTarget);
    }

    /**
     * Calculate next billing date
     * 
     * @param string $period ('week' or 'month')
     * @param int $interval
     * @return Carbon
     */
    protected function calculateNextBilling($period, $interval = 1)
    {
        $date = Carbon::now();
        
        if ($period === 'week') {
            return $date->addWeeks($interval);
        } elseif ($period === 'month') {
            return $date->addMonths($interval);
        }
        
        return $date->addMonth(); // Default
    }

    /**
     * Migrate a WooCommerce subscription to Laravel
     * 
     * POST /api/subscriptions/migrate
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function migrateFromWooCommerce(Request $request)
    {
        // Accept either wordpress_user_id or woocommerce_customer_id
        $user_id = $request->input('wordpress_user_id') ?? $request->input('woocommerce_customer_id');
        
        Log::info('Migration endpoint reached', [
            'wc_subscription_id' => $request->input('woocommerce_subscription_id'),
            'user_id' => $user_id
        ]);
        
        $validated = $request->validate([
            'woocommerce_subscription_id' => 'required|integer',
            'woocommerce_customer_id' => 'required_without:wordpress_user_id|integer',
            'wordpress_user_id' => 'required_without:woocommerce_customer_id|integer',
            'customer_email' => 'required|email',
            'customer_first_name' => 'nullable|string',
            'customer_last_name' => 'nullable|string',
            'product_id' => 'nullable|integer',
            'product_name' => 'required|string',
            'variation_id' => 'nullable|integer',
            'status' => 'required|string',
            'billing_frequency' => 'required', // Accept integer or string (1 or "1" or "weekly")
            'billing_period' => 'nullable|string',
            'delivery_frequency' => 'nullable|string',
            'delivery_day' => 'nullable|string',
            'price' => 'nullable|numeric',
            'total' => 'nullable|numeric',
            'next_payment_date' => 'nullable|date',
            'start_date' => 'nullable|date',
            'trial_end_date' => 'nullable|date',
            'billing_address' => 'nullable|array',
            'shipping_address' => 'nullable|array',
        ]);
        
        // Use the resolved user_id in validated array
        $validated['wordpress_user_id'] = $user_id;

        try {
            // Check if already migrated (prevent duplicates)
            $existing = VegboxSubscription::where('woo_subscription_id', $validated['woocommerce_subscription_id'])->first();

            Log::info('Duplicate check result', [
                'wc_subscription_id' => $validated['woocommerce_subscription_id'],
                'existing_found' => $existing ? 'yes' : 'no',
                'existing_id' => $existing->id ?? null
            ]);

            if ($existing) {
                Log::info('Subscription migration skipped - already exists', [
                    'wc_subscription_id' => $validated['woocommerce_subscription_id'],
                    'laravel_subscription_id' => $existing->id
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Subscription already migrated',
                    'subscription_id' => $existing->id,
                    'subscription' => $existing
                ], 200);
            }

            // Find or create matching vegbox plan
            $plan = null;
            if (!empty($validated['variation_id'])) {
                $plan = VegboxPlan::where('id', $validated['variation_id'])->first();
            }
            
            // If no plan by variation_id, try to find existing plan by price and period
            if (!$plan) {
                $invoice_period = $this->mapBillingPeriodToDays($validated['billing_period'] ?? 'week');
                $price = $validated['price'] ?? $validated['total'] ?? 0;
                
                $plan = VegboxPlan::where('price', $price)
                    ->where('invoice_period', $invoice_period)
                    ->where('is_active', true)
                    ->first();
                
                if ($plan) {
                    Log::info('Found existing plan by price/period match', [
                        'plan_id' => $plan->id,
                        'plan_name' => $plan->name,
                        'price' => $price,
                        'period' => $invoice_period
                    ]);
                }
            }
            
            // If still no plan, use any existing plan as placeholder (plan_id is NOT NULL)
            if (!$plan) {
                $plan = VegboxPlan::first();
                if (!$plan) {
                    throw new \Exception('No vegbox plans exist - create at least one plan first');
                }
                Log::warning('No plan_id for migration, using placeholder', [
                    'wc_subscription_id' => $validated['woocommerce_subscription_id'],
                    'placeholder_plan_id' => $plan->id
                ]);
            }

            // Map WooCommerce status to Laravel status fields
            $statusMapping = $this->mapWooCommerceStatus($validated['status']);
            
            Log::info('Status mapping result', [
                'wc_status' => $validated['status'],
                'mapping' => $statusMapping
            ]);

            // Prepare subscription data
            $subscriptionData = [
                'wordpress_user_id' => $validated['wordpress_user_id'],
                'woo_subscription_id' => $validated['woocommerce_subscription_id'],
                'woocommerce_subscription_id' => $validated['woocommerce_subscription_id'], // New unified field
                'wc_order_id' => $validated['wc_order_id'] ?? null, // WooCommerce order ID if available
                'woo_product_id' => $validated['product_id'] ?? null,
                'woo_variation_id' => $validated['variation_id'] ?? null,
                'plan_id' => $plan->id ?? null,
                'slug' => 'vegbox-' . $validated['woocommerce_subscription_id'],
                'name' => $validated['product_name'],
                'description' => 'Migrated from WooCommerce Subscriptions',
                'price' => $validated['price'] ?? $validated['total'] ?? 0,
                'currency' => 'GBP',
                'billing_frequency' => $validated['billing_frequency'],
                'billing_period' => $validated['billing_period'] ?? 'week',
                'delivery_day' => $validated['delivery_day'] ?? 'monday', // Default to monday if not provided
                // Note: frequency column doesn't exist, using billing_frequency instead
                'starts_at' => isset($validated['start_date']) ? Carbon::parse($validated['start_date']) : now(),
                'trial_ends_at' => isset($validated['trial_end_date']) ? Carbon::parse($validated['trial_end_date']) : null,
                'next_billing_at' => isset($validated['next_payment_date']) ? Carbon::parse($validated['next_payment_date']) : null,
                'canceled_at' => $statusMapping['canceled_at'],
                'ends_at' => $statusMapping['ends_at'],
                'pause_until' => $statusMapping['pause_until'],
                'imported_from_woo' => true,
                'skip_auto_renewal' => true, // Skip auto-renewal during migration period
            ];

            Log::info('Attempting to create subscription', [
                'wc_subscription_id' => $validated['woocommerce_subscription_id'],
                'data' => $subscriptionData
            ]);

            // Create new subscription
            $subscription = VegboxSubscription::create($subscriptionData);

            // Debug: Check if subscription was actually created
            if (!$subscription || !$subscription->exists) {
                Log::error('Subscription create() returned but not persisted', [
                    'wc_subscription_id' => $validated['woocommerce_subscription_id'],
                    'subscription_object' => $subscription ? 'exists' : 'null',
                    'subscription_id' => $subscription->id ?? 'null'
                ]);
                throw new \Exception('Subscription was not persisted to database');
            }

            // Refresh from database to ensure we have the ID
            $subscription->refresh();
            
            // Fix dates that parent's boot() method incorrectly modified
            if ($subscription->imported_from_woo) {
                $subscription->starts_at = $subscriptionData['starts_at'];
                $subscription->ends_at = $subscriptionData['ends_at'];
                $subscription->saveQuietly();
            }

            Log::info('Subscription migrated successfully', [
                'wc_subscription_id' => $validated['woocommerce_subscription_id'],
                'laravel_subscription_id' => $subscription->id,
                'wordpress_user_id' => $validated['wordpress_user_id'],
                'status' => $validated['status']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Subscription migrated successfully',
                'subscription_id' => $subscription->id,
                'subscription' => $subscription
            ], 201);

        } catch (\Exception $e) {
            Log::error('Subscription migration failed', [
                'wc_subscription_id' => $validated['woocommerce_subscription_id'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Migration failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Map WooCommerce status to Laravel subscription fields
     * 
     * @param string $wcStatus
     * @return array
     */
    protected function mapWooCommerceStatus($wcStatus)
    {
        $now = Carbon::now();

        switch ($wcStatus) {
            case 'active':
                return [
                    'status' => 'active',
                    'canceled_at' => null,
                    'ends_at' => null,
                    'pause_until' => null,
                ];

            case 'on-hold':
            case 'paused':
                return [
                    'status' => 'active',
                    'canceled_at' => null,
                    'ends_at' => null,
                    'pause_until' => $now->copy()->addMonth(), // Pause for 1 month default
                ];

            case 'cancelled':
            case 'canceled':
                return [
                    'status' => 'canceled',
                    'canceled_at' => $now,
                    'ends_at' => $now,
                    'pause_until' => null,
                ];

            case 'expired':
                return [
                    'status' => 'expired',
                    'canceled_at' => null,
                    'ends_at' => $now->copy()->subDay(),
                    'pause_until' => null,
                ];

            case 'pending-cancel':
                return [
                    'status' => 'active',
                    'canceled_at' => null,
                    'ends_at' => $now->copy()->addWeek(), // Will expire next week
                    'pause_until' => null,
                ];

            default:
                return [
                    'status' => 'active',
                    'canceled_at' => null,
                    'ends_at' => null,
                    'pause_until' => null,
                ];
        }
    }

    /**
     * Map billing period to invoice period days
     * 
     * @param string $period
     * @return int
     */
    protected function mapBillingPeriodToDays($period)
    {
        switch (strtolower($period)) {
            case 'day':
                return 1;
            case 'week':
                return 7;
            case 'month':
                return 30;
            case 'year':
                return 365;
            default:
                return 7; // Default to weekly
        }
    }

    /**
     * Change subscription delivery method (delivery <-> collection)
     * 
     * @param Request $request
     * @param int $id Subscription ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function changeDeliveryMethod(Request $request, $id)
    {
        try {
            $subscription = VegboxSubscription::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'delivery_method' => 'required|in:delivery,collection'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid delivery method',
                    'errors' => $validator->errors()
                ], 422);
            }

            $newDeliveryMethod = $request->input('delivery_method');
            
            // Find the appropriate plan based on current subscription attributes
            $newPlan = VegboxPlan::where('box_size', $subscription->box_size)
                ->where('invoice_period', $subscription->billing_period) // 'week', 'month', 'year'
                ->where(function($query) use ($newDeliveryMethod) {
                    // Check if plan name or slug indicates delivery method
                    if ($newDeliveryMethod === 'delivery') {
                        $query->where('slug', 'like', '%delivery%')
                              ->orWhere('name', 'like', '%delivery%');
                    } else {
                        $query->where('slug', 'like', '%collection%')
                              ->orWhere('name', 'like', '%collection%');
                    }
                })
                ->first();

            if (!$newPlan) {
                return response()->json([
                    'success' => false,
                    'message' => 'No plan found for the requested delivery method'
                ], 404);
            }

            // Update subscription
            $subscription->plan_id = $newPlan->id;
            $subscription->price = $newPlan->price;
            $subscription->delivery_method = $newDeliveryMethod;
            $subscription->save();

            // Sync to WooCommerce if needed
            if ($subscription->woo_subscription_id) {
                $this->syncToWooCommerce($subscription);
            }

            return response()->json([
                'success' => true,
                'message' => 'Delivery method updated successfully',
                'subscription' => [
                    'id' => $subscription->id,
                    'delivery_method' => $subscription->delivery_method,
                    'price' => $subscription->price,
                    'plan_name' => $newPlan->name
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error changing delivery method: ' . $e->getMessage(), [
                'subscription_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update delivery method: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Change subscription billing frequency (weekly <-> monthly)
     * 
     * @param Request $request
     * @param int $id Subscription ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function changeBillingFrequency(Request $request, $id)
    {
        try {
            $subscription = VegboxSubscription::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'billing_frequency' => 'required|in:weekly,fortnightly,monthly,annual',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid billing frequency',
                    'errors' => $validator->errors()
                ], 422);
            }

            $billingFrequency = $request->input('billing_frequency'); // 'weekly', 'fortnightly', 'monthly', 'annual'
            
            // Map frequency to period and interval
            $periodMapping = [
                'weekly' => ['period' => 'week', 'interval' => 1],
                'fortnightly' => ['period' => 'week', 'interval' => 2],
                'monthly' => ['period' => 'month', 'interval' => 1],
                'annual' => ['period' => 'year', 'interval' => 1],
            ];
            
            $newPeriod = $periodMapping[$billingFrequency]['period'];
            $newInterval = $periodMapping[$billingFrequency]['interval'];
            
            // Determine delivery method from current subscription
            $deliveryMethod = $subscription->delivery_method ?? 'delivery';
            
            // Find the appropriate plan by matching slug pattern
            $searchPattern = '%' . $billingFrequency . '%';
            
            $newPlan = VegboxPlan::where('box_size', $subscription->box_size)
                ->where(function($query) use ($searchPattern, $deliveryMethod) {
                    // Match billing frequency in slug
                    $query->where('slug', 'like', $searchPattern)
                          ->orWhere('name', 'like', $searchPattern);
                })
                ->where(function($query) use ($deliveryMethod) {
                    // Match delivery method
                    if ($deliveryMethod === 'delivery') {
                        $query->where('slug', 'like', '%delivery%')
                              ->orWhere('name', 'like', '%delivery%')
                              ->orWhereNotIn('slug', function($q) {
                                  $q->select('slug')->from('vegbox_plans')->where('slug', 'like', '%collection%');
                              });
                    } else {
                        $query->where('slug', 'like', '%collection%')
                              ->orWhere('name', 'like', '%collection%');
                    }
                })
                ->first();

            if (!$newPlan) {
                return response()->json([
                    'success' => false,
                    'message' => 'No plan found for the requested billing frequency'
                ], 404);
            }

            // Calculate new next billing date based on new frequency
            $now = Carbon::now();
            $nextBillingAt = match($billingFrequency) {
                'weekly' => $now->addWeek(),
                'fortnightly' => $now->addWeeks(2),
                'monthly' => $now->addMonth(),
                'annual' => $now->addYear(),
                default => $now->addWeek()
            };

            // Update subscription
            $subscription->plan_id = $newPlan->id;
            $subscription->price = $newPlan->price;
            $subscription->billing_period = $newPeriod;
            $subscription->billing_frequency = $newInterval;
            $subscription->next_billing_at = $nextBillingAt;
            $subscription->save();

            // Sync to WooCommerce if needed
            if ($subscription->woo_subscription_id) {
                $this->syncToWooCommerce($subscription);
            }

            return response()->json([
                'success' => true,
                'message' => 'Billing frequency updated successfully',
                'subscription' => [
                    'id' => $subscription->id,
                    'billing_period' => $subscription->billing_period,
                    'billing_frequency' => $subscription->billing_frequency,
                    'price' => $subscription->price,
                    'next_billing_at' => $subscription->next_billing_at->format('Y-m-d'),
                    'plan_name' => $newPlan->name
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error changing billing frequency: ' . $e->getMessage(), [
                'subscription_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update billing frequency: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sync subscription changes back to WooCommerce
     * 
     * @param VegboxSubscription $subscription
     * @return void
     */
    protected function syncToWooCommerce(VegboxSubscription $subscription)
    {
        // TODO: Implement WooCommerce sync via REST API or direct database update
        // For now, just log the sync requirement
        Log::info('Subscription needs WooCommerce sync', [
            'subscription_id' => $subscription->id,
            'woo_subscription_id' => $subscription->woo_subscription_id
        ]);
    }
}
