<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CsaSubscription;
use App\Models\VegboxPlan;
use App\Models\SubscriptionAudit;
use App\Services\VegboxPaymentService;
use App\Notifications\SubscriptionCancelled;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class VegboxSubscriptionController extends Controller
{
    protected VegboxPaymentService $paymentService;

    public function __construct(VegboxPaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Dashboard overview
     */
    public function index(Request $request)
    {
        $query = CsaSubscription::query();

        // Filter by status
        if ($request->has('status')) {
            switch ($request->status) {
                case 'active':
                    $query->active();
                    break;
                case 'cancelled':
                    $query->cancelled();
                    break;
                case 'expired':
                    $query->where('status', 'expired');
                    break;
            }
        }

        // Search by customer email or name
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('customer_email', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%");
            });
        }

        $subscriptions = $query->latest()->paginate(20);

        $windowStart = Carbon::now();
        $windowEnd = $windowStart->copy()->addDays(7)->endOfDay();

        // Statistics - cached for 5 minutes
        $stats = Cache::remember('csa_subscription_stats', 300, function () use ($windowStart, $windowEnd) {
            return [
                'total_active' => CsaSubscription::active()->count(),
                'total_cancelled' => CsaSubscription::cancelled()->count(),
                'upcoming_renewals' => CsaSubscription::active()
                    ->whereNotNull('next_billing_date')
                    ->whereBetween('next_billing_date', [$windowStart, $windowEnd])
                    ->count(),
                'failed_last_24h' => $this->getFailedPaymentsCount(24),
            ];
        });

        return view('admin.vegbox-subscriptions.index', compact('subscriptions', 'stats'));
    }

    /**
     * Show upcoming renewals
     */
    public function upcomingRenewals(Request $request)
    {
        $days = $request->get('days', 7);
        $windowStart = Carbon::now();
        $windowEnd = $windowStart->copy()->addDays($days)->endOfDay();

        $renewals = CsaSubscription::active()
            ->whereNotNull('next_billing_date')
            ->whereBetween('next_billing_date', [$windowStart, $windowEnd])
            ->orderBy('next_billing_date')
            ->paginate(20);

        return view('admin.vegbox-subscriptions.upcoming-renewals', compact('renewals', 'days'));
    }

    /**
     * Show failed payments
     */
    public function failedPayments(Request $request)
    {
        $hours = $request->get('hours', 48);

        // Get subscriptions with failed payment count > 0 (matches sidebar badge query)
        $subscriptions = CsaSubscription::where('failed_payment_count', '>', 0)
            ->where('status', '!=', 'cancelled')
            ->orderBy('failed_payment_count', 'desc')
            ->orderBy('last_payment_date', 'desc')
            ->paginate(20);

        return view('admin.vegbox-subscriptions.failed-payments', compact('subscriptions', 'hours'));
    }

    /**
     * Show subscription details
     */
    public function show($id)
    {
        $subscription = CsaSubscription::with(['deliveries'])
            ->findOrFail($id);

        // Get payment history from logs
        $paymentHistory = $this->getPaymentHistory($subscription);

        // Check customer balance (only for subscriptions with Laravel subscribers)
        $balanceInfo = null;
        if ($subscription->customer_id) {
            $balanceInfo = $this->paymentService->checkCustomerBalance($subscription->customer_id);
        }

        // Get customer payment methods (only for subscriptions with Laravel subscribers)
        $paymentMethodsInfo = null;
        if ($subscription->customer_id) {
            $paymentMethodsInfo = $this->paymentService->getPaymentMethods($subscription->customer_id);
        }

        return view('admin.vegbox-subscriptions.show', compact('subscription', 'paymentHistory', 'balanceInfo', 'paymentMethodsInfo'));
    }

    /**
     * Manual renewal attempt
     */
    public function manualRenewal($id)
    {
        $subscription = CsaSubscription::findOrFail($id);

        try {
            $result = $this->paymentService->processSubscriptionRenewal($subscription);

            if ($result['success']) {
                // Calculate next billing date based on subscription's billing frequency
                $nextBilling = $subscription->next_billing_at ? 
                    Carbon::parse($subscription->next_billing_at) : 
                    now();
                
                // Cast billing_frequency to int to avoid type errors
                $frequency = (int) ($subscription->billing_frequency ?? 1);
                
                // Add the subscription's billing period
                if ($subscription->billing_period === 'week') {
                    $nextBilling->addWeeks($frequency);
                } elseif ($subscription->billing_period === 'month') {
                    $nextBilling->addMonths($frequency);
                } elseif ($subscription->billing_period === 'year') {
                    $nextBilling->addYears($frequency);
                } else {
                    $nextBilling->addMonth(); // Default fallback
                }
                
                // Christmas closure: Dec 21, 2025 - May 1, 2026
                // If next billing would be during closure, skip to April 10
                $closureStart = Carbon::parse('2025-12-21');
                $closureEnd = Carbon::parse('2026-05-01');
                $resumeBilling = Carbon::parse('2026-04-10');
                
                if ($nextBilling->between($closureStart, $closureEnd)) {
                    $nextBilling = $resumeBilling;
                    $subscription->update(['skip_auto_renewal' => true]);
                }
                
                $subscription->update([
                    'next_billing_at' => $nextBilling
                ]);
                
                // Audit log
                SubscriptionAudit::log(
                    $subscription,
                    'renewed',
                    'Manual renewal processed successfully',
                    null,
                    ['next_billing_at' => $nextBilling->toDateTimeString()],
                    ['transaction_id' => $result['transaction_id']]
                );

                return redirect()
                    ->route('admin.vegbox-subscriptions.show', $id)
                    ->with('success', 'Subscription renewed successfully! Transaction ID: ' . $result['transaction_id'] . '. Next billing: ' . $nextBilling->format('M j, Y'));
            } else {
                return redirect()
                    ->route('admin.vegbox-subscriptions.show', $id)
                    ->with('error', 'Renewal failed: ' . $result['error']);
            }
        } catch (\Exception $e) {
            Log::error('Manual renewal failed', [
                'subscription_id' => $id,
                'error' => $e->getMessage()
            ]);

            return redirect()
                ->route('admin.vegbox-subscriptions.show', $id)
                ->with('error', 'Error processing renewal: ' . $e->getMessage());
        }
    }

    /**
     * Cancel subscription
     */
    public function cancel($id)
    {
        $subscription = CsaSubscription::findOrFail($id);

        $subscription->update([
            'canceled_at' => now(),
            'ends_at' => now()->addDays(30) // Grace period
        ]);
        
        // Audit log
        SubscriptionAudit::log(
            $subscription,
            'cancelled',
            'Subscription cancelled by admin',
            null,
            ['canceled_at' => now()->toDateTimeString(), 'ends_at' => now()->addDays(30)->toDateTimeString()]
        );
        
        // Invalidate subscription stats cache
        Cache::forget('vegbox_subscription_stats');

        // Send cancellation notification to customer
        $user = $subscription->subscriber;
        if ($user) {
            $user->notify(new SubscriptionCancelled($subscription, 'Manual cancellation by admin', false));
        }

        return redirect()
            ->route('admin.vegbox-subscriptions.index')
            ->with('success', 'Subscription cancelled. Will end on ' . $subscription->ends_at->format('d/m/Y'));
    }

    /**
     * Reactivate subscription
     */
    public function reactivate($id)
    {
        $subscription = CsaSubscription::findOrFail($id);

        $subscription->update([
            'canceled_at' => null,
            'ends_at' => null,
            'next_billing_at' => now()->addMonth()
        ]);
        
        // Audit log
        SubscriptionAudit::log(
            $subscription,
            'reactivated',
            'Subscription reactivated by admin',
            ['canceled_at' => $subscription->canceled_at?->toDateTimeString()],
            ['next_billing_at' => now()->addMonth()->toDateTimeString()]
        );
        
        // Invalidate subscription stats cache
        Cache::forget('vegbox_subscription_stats');

        return redirect()
            ->route('admin.vegbox-subscriptions.show', $id)
            ->with('success', 'Subscription reactivated successfully!');
    }

    /**
     * Change subscription plan (upgrade/downgrade)
     */
    public function changePlan(Request $request, $id)
    {
        $request->validate([
            'new_plan_id' => 'required|exists:vegbox_plans,id',
            'prorate' => 'boolean',
            'change_immediately' => 'boolean',
        ]);

        $subscription = CsaSubscription::findOrFail($id);
        $newPlan = VegboxPlan::findOrFail($request->new_plan_id);
        $oldPlan = $subscription->plan;
        $oldPrice = $subscription->price;

        // Check if it's actually a different plan
        if ($subscription->plan_id == $request->new_plan_id) {
            return redirect()
                ->route('admin.vegbox-subscriptions.show', $id)
                ->with('error', 'Subscription is already on this plan');
        }

        try {
            $changeImmediately = $request->boolean('change_immediately', true);
            $prorate = $request->boolean('prorate', true);

            if ($changeImmediately) {
                // Change plan immediately
                $prorateAmount = null;
                
                if ($prorate && $subscription->next_billing_at) {
                    // Calculate prorated amount
                    $daysUsed = now()->diffInDays($subscription->starts_at ?? $subscription->created_at);
                    $totalDays = ($subscription->starts_at ?? $subscription->created_at)
                        ->diffInDays($subscription->next_billing_at);
                    
                    if ($totalDays > 0) {
                        $daysRemaining = max(0, $totalDays - $daysUsed);
                        $unusedAmount = ($oldPrice / $totalDays) * $daysRemaining;
                        $newPeriodCost = $newPlan->price;
                        $prorateAmount = $newPeriodCost - $unusedAmount;
                        
                        // If upgrade (more expensive), charge the difference
                        if ($prorateAmount > 0) {
                            // Process prorated payment
                            $result = $this->paymentService->processSubscriptionRenewal($subscription);
                            
                            if (!$result['success']) {
                                return redirect()
                                    ->route('admin.vegbox-subscriptions.show', $id)
                                    ->with('error', 'Failed to process prorated payment: ' . $result['error']);
                            }
                        }
                    }
                }
                
                // Update subscription
                $subscription->update([
                    'plan_id' => $newPlan->id,
                    'name' => $newPlan->name,
                    'description' => $newPlan->description,
                    'price' => $newPlan->price,
                    'billing_frequency' => $newPlan->invoice_period,
                    'billing_period' => $newPlan->invoice_interval,
                ]);
                
                // Audit log
                SubscriptionAudit::log(
                    $subscription,
                    'plan_changed',
                    "Plan changed from {$oldPlan->name} to {$newPlan->name}",
                    [
                        'old_plan_id' => $oldPlan->id,
                        'old_price' => $oldPrice,
                    ],
                    [
                        'new_plan_id' => $newPlan->id,
                        'new_price' => $newPlan->price,
                    ],
                    [
                        'prorated' => $prorate,
                        'prorate_amount' => $prorateAmount,
                    ]
                );
                
                $message = "Plan changed from {$oldPlan->name} to {$newPlan->name}";
                if ($prorateAmount > 0) {
                    $message .= sprintf(' (prorated charge: £%.2f)', $prorateAmount);
                } elseif ($prorateAmount < 0) {
                    $message .= sprintf(' (credit applied: £%.2f)', abs($prorateAmount));
                }
                
            } else {
                // Schedule plan change for next billing
                $subscription->update([
                    'scheduled_plan_id' => $newPlan->id,
                ]);
                
                $message = "Plan change scheduled for next billing date: {$oldPlan->name} → {$newPlan->name}";
            }

            // Invalidate subscription stats cache
            Cache::forget('vegbox_subscription_stats');

            Log::info('Subscription plan changed', [
                'subscription_id' => $id,
                'old_plan' => $oldPlan->name,
                'new_plan' => $newPlan->name,
                'immediate' => $changeImmediately,
                'prorated' => $prorate,
            ]);

            return redirect()
                ->route('admin.vegbox-subscriptions.show', $id)
                ->with('success', $message);

        } catch (\Exception $e) {
            Log::error('Failed to change subscription plan', [
                'subscription_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('admin.vegbox-subscriptions.show', $id)
                ->with('error', 'Failed to change plan: ' . $e->getMessage());
        }
    }

    /**
     * Get payment history from Laravel logs and WooCommerce (for imported subscriptions)
     */
    protected function getPaymentHistory(CsaSubscription $subscription)
    {
        $history = [];

        // Get Laravel log entries for this subscription
        $logFile = storage_path('logs/laravel.log');
        if (file_exists($logFile)) {
            $logs = file($logFile);
            $subscriptionId = $subscription->id;

            foreach ($logs as $line) {
                // Look for subscription renewal logs
                if (strpos($line, "subscription_id") !== false && 
                    (strpos($line, "\"subscription_id\":{$subscriptionId}") !== false ||
                     strpos($line, "\"subscription_id\":\"{$subscriptionId}\"") !== false ||
                     strpos($line, "subscription_id\":{$subscriptionId}") !== false)) {
                    
                    // Extract timestamp from log line
                    if (preg_match('/\[(.*?)\]/', $line, $timeMatch)) {
                        $timestamp = $timeMatch[1];
                        
                        // Parse JSON context
                        if (preg_match('/\{.*\}/', $line, $matches)) {
                            $logData = json_decode($matches[0], true);
                            if ($logData) {
                                // Determine status from log message
                                $status = 'unknown';
                                $message = 'Subscription activity';
                                
                                if (strpos($line, 'renewal payment successful') !== false) {
                                    $status = 'success';
                                    $message = 'Subscription renewal payment';
                                } elseif (strpos($line, 'payment failed') !== false) {
                                    $status = 'failed';
                                    $message = 'Renewal payment failed';
                                } elseif (strpos($line, 'Insufficient funds') !== false) {
                                    $status = 'failed';
                                    $message = 'Insufficient funds';
                                } elseif (strpos($line, 'Processing vegbox subscription renewal') !== false) {
                                    continue; // Skip processing logs, only show results
                                } elseif (strpos($line, 'ERROR') !== false || strpos($line, 'SQLSTATE') !== false) {
                                    continue; // Skip error logs
                                } else {
                                    continue; // Skip unknown activity logs
                                }
                                
                                $history[] = [
                                    'timestamp' => $timestamp,
                                    'message' => $message,
                                    'status' => $status,
                                    'transaction_id' => $logData['transaction_id'] ?? null,
                                    'amount' => $logData['amount'] ?? null,
                                    'channel' => $logData['channel'] ?? null,
                                    'source' => 'laravel'
                                ];
                            }
                        }
                    }
                }
            }
        }

        // Get WooCommerce payment history for imported subscriptions
        if ($subscription->woo_subscription_id) {
            try {
                $wooOrders = DB::connection('wordpress')
                    ->table('posts as p')
                    ->join('postmeta as pm', 'p.ID', '=', 'pm.post_id')
                    ->where('p.post_type', 'shop_order')
                    ->where('pm.meta_key', '_subscription_renewal')
                    ->where('pm.meta_value', $subscription->woo_subscription_id)
                    ->select('p.ID', 'p.post_date', 'p.post_status')
                    ->orderBy('p.post_date', 'desc')
                    ->limit(10)
                    ->get();

                foreach ($wooOrders as $order) {
                    $history[] = [
                        'timestamp' => $order->post_date,
                        'message' => 'WooCommerce renewal order',
                        'status' => $order->post_status === 'wc-completed' ? 'success' : 'pending',
                        'transaction_id' => $order->ID,
                        'source' => 'woocommerce'
                    ];
                }
            } catch (\Exception $e) {
                // Log error but don't fail
                \Log::error('Failed to fetch WooCommerce payment history: ' . $e->getMessage());
            }
        }

        // Sort by timestamp descending and return last 15
        usort($history, function($a, $b) {
            $aTime = isset($a['timestamp']) ? strtotime($a['timestamp']) : 0;
            $bTime = isset($b['timestamp']) ? strtotime($b['timestamp']) : 0;
            return $bTime - $aTime;
        });

        return array_slice($history, 0, 15);
    }

    /**
     * Get count of failed payments in last X hours
     */
    protected function getFailedPaymentsCount(int $hours): int
    {
        $cutoff = now()->subHours($hours);
        
        return CsaSubscription::where('failed_payment_count', '>', 0)
            ->where('last_payment_date', '>=', $cutoff)
            ->count();
    }
}
