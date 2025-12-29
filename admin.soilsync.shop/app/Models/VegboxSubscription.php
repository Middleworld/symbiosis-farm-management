<?php

namespace App\Models;

use Laravelcm\Subscriptions\Models\Subscription as BaseSubscription;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class VegboxSubscription extends BaseSubscription
{
    protected $table = 'vegbox_subscriptions';

    protected $fillable = [
        'subscriber_id',
        'subscriber_type',
        'vegbox_plan_id',
        'slug',
        'name',
        'description',
        'price',
        'currency',
        'trial_ends_at',
        'starts_at',
        'ends_at',
        'cancels_at',
        'canceled_at',
        'grace_ends_at',
        'next_billing_at',
        'billing_frequency',
        'billing_period',
        // Vegbox-specific fields
        'box_type',
        'box_size',
        'frequency',
        'status',
        'delivery_method',
        'delivery_day', // monday, tuesday, wednesday, etc.
        'delivery_time', // morning, afternoon, evening
        'delivery_address_id',
        'special_instructions',
        'pause_until', // for temporary pauses
        'total_deliveries',
        'next_delivery_date',
        // WooCommerce import fields
        'wordpress_user_id',
        'woo_subscription_id',
        'woocommerce_subscription_id',
        'wc_order_id',
        'woocommerce_product_id',
        'imported_from_woo',
        'skip_auto_renewal',
        // Retry tracking fields
        'failed_payment_count',
        'last_payment_attempt_at',
        'next_retry_at',
        'last_payment_error',
        'grace_period_ends_at',
        // Payment tracking
        'payment_intent_id',
        'stripe_customer_id', // Added for migration
    ];

    protected $casts = [
        'name' => 'json',
        'description' => 'json',
        'price' => 'decimal:2',
        'trial_ends_at' => 'datetime',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'cancels_at' => 'datetime',
        'canceled_at' => 'datetime',
        'grace_ends_at' => 'datetime',
        'next_billing_at' => 'datetime',
        'pause_until' => 'datetime',
        'next_delivery_date' => 'datetime',
        'last_payment_attempt_at' => 'datetime',
        'next_retry_at' => 'datetime',
        'grace_period_ends_at' => 'datetime',
        'failed_payment_count' => 'integer',
        'total_deliveries' => 'integer',
    ];

    /**
     * Boot the model and override base subscription behavior
     */
    protected static function boot(): void
    {
        parent::boot();
        
        // Override parent's creating listener for imported/POS subscriptions
        static::creating(function ($subscription) {
            // For imported WooCommerce subscriptions, skip parent's period logic
            if ($subscription->imported_from_woo) {
                return true; // Allow creation but parent setNewPeriod won't run for these
            }
            
            // For POS subscriptions without a plan, set billing manually
            if (!$subscription->vegbox_plan_id) {
                $subscription->starts_at = $subscription->starts_at ?? now();
                
                // Calculate next billing date based on frequency and period
                if ($subscription->billing_frequency && $subscription->billing_period) {
                    $nextBilling = $subscription->starts_at->copy();
                    switch ($subscription->billing_period) {
                        case 'day':
                            $nextBilling->addDays($subscription->billing_frequency);
                            break;
                        case 'week':
                            $nextBilling->addWeeks($subscription->billing_frequency);
                            break;
                        case 'month':
                            $nextBilling->addMonths($subscription->billing_frequency);
                            break;
                        case 'year':
                            $nextBilling->addYears($subscription->billing_frequency);
                            break;
                    }
                    
                    $subscription->next_billing_at = $nextBilling;
                }
                
                // Prevent parent's event from running
                return false;
            }
            
            // For subscriptions with plans, let parent handle it
            return true;
        }, 999); // High priority to run before parent's listener
    }

    /**
     * Override parent's setNewPeriod to handle subscriptions without plans
     */
    public function setNewPeriod(string $invoice_interval = '', ?int $invoice_period = null, ?\Carbon\Carbon $start = null): \Laravelcm\Subscriptions\Models\Subscription
    {
        // If no plan, handle billing period manually
        if (!$this->vegbox_plan_id || !$this->plan) {
            $this->starts_at = $start ?? $this->starts_at ?? now();
            
            if ($this->billing_frequency && $this->billing_period) {
                $nextBilling = $this->starts_at->copy();
                
                switch ($this->billing_period) {
                    case 'day':
                        $nextBilling->addDays($this->billing_frequency);
                        break;
                    case 'week':
                        $nextBilling->addWeeks($this->billing_frequency);
                        break;
                    case 'month':
                        $nextBilling->addMonths($this->billing_frequency);
                        break;
                    case 'year':
                        $nextBilling->addYears($this->billing_frequency);
                        break;
                }
                
                $this->next_billing_at = $nextBilling;
            }
            
            return $this;
        }
        
        // If we have a plan, use parent's logic
        return parent::setNewPeriod($invoice_interval, $invoice_period, $start);
    }

    /**
     * Get the vegbox plan for this subscription.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(VegboxPlan::class, 'vegbox_plan_id');
    }

    /**
     * Get the delivery schedules for this subscription.
     */
    public function deliverySchedules(): HasMany
    {
        return $this->hasMany(DeliverySchedule::class);
    }

    /**
     * Get the subscriber (user) for this subscription.
     */
    public function subscriber(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope for active vegbox subscriptions.
     */
    public function scopeActive($query)
    {
        return $query->whereNull('canceled_at')
                    ->where('starts_at', '<=', now())
                    ->where(function ($q) {
                        $q->whereNull('ends_at')
                          ->orWhere('ends_at', '>', now());
                    })
                    ->whereDoesntHave('plan', function ($q) {
                        $q->where('name', 'like', '%Test%');
                    });
    }

    /**
     * Scope for subscriptions with upcoming deliveries.
     */
    public function scopeWithUpcomingDeliveries($query, $days = 7)
    {
        return $query->where('next_delivery_date', '<=', now()->addDays($days))
                    ->whereNull('pause_until');
    }

    /**
     * Check if subscription is currently paused.
     */
    public function isPaused(): bool
    {
        return $this->pause_until && $this->pause_until->isFuture();
    }

    /**
     * Pause the subscription until a specific date.
     */
    public function pauseUntil(Carbon $date): void
    {
        $this->update(['pause_until' => $date]);
        $this->recalculateNextDelivery();
    }

    /**
     * Resume the subscription.
     */
    public function resume(): void
    {
        $this->update(['pause_until' => null]);
        $this->recalculateNextDelivery();
    }

    /**
     * Recalculate the next delivery date based on delivery day and pause status.
     */
    public function recalculateNextDelivery(): void
    {
        if ($this->isPaused()) {
            $this->update(['next_delivery_date' => null]);
            return;
        }

        $nextDelivery = $this->calculateNextDeliveryDate();
        $this->update(['next_delivery_date' => $nextDelivery]);
    }

    /**
     * Calculate the next delivery date based on plan frequency and delivery day.
     */
    private function calculateNextDeliveryDate(): ?Carbon
    {
        if (!$this->delivery_day || !$this->plan) {
            return null;
        }

        $today = now();
        $deliveryDayIndex = $this->getDayIndex($this->delivery_day);

        // Start from today and find the next occurrence of the delivery day
        $nextDelivery = $today->copy();

        // If today is the delivery day and we haven't delivered yet today, use today
        if ($nextDelivery->dayOfWeek === $deliveryDayIndex) {
            return $nextDelivery;
        }

        // Find the next occurrence
        while ($nextDelivery->dayOfWeek !== $deliveryDayIndex) {
            $nextDelivery->addDay();
        }

        // If the plan is bi-weekly, we need to check the frequency
        if ($this->plan->delivery_frequency === 'bi-weekly') {
            // For simplicity, alternate weeks - this could be enhanced
            $weeksSinceStart = $this->starts_at->diffInWeeks($nextDelivery);
            if ($weeksSinceStart % 2 !== 0) {
                $nextDelivery->addWeeks(1);
            }
        }

        return $nextDelivery;
    }

    /**
     * Get the day index for Carbon (0 = Sunday, 1 = Monday, etc.)
     */
    private function getDayIndex(string $day): int
    {
        return match(strtolower($day)) {
            'sunday' => 0,
            'monday' => 1,
            'tuesday' => 2,
            'wednesday' => 3,
            'thursday' => 4,
            'friday' => 5,
            'saturday' => 6,
            default => 1, // Default to Monday
        };
    }

    /**
     * Get the delivery day display name.
     */
    public function getDeliveryDayDisplayAttribute(): string
    {
        return ucfirst($this->delivery_day ?? 'Not set');
    }

    /**
     * Get the delivery time display name.
     */
    public function getDeliveryTimeDisplayAttribute(): string
    {
        return match($this->delivery_time) {
            'morning' => 'Morning (9AM-12PM)',
            'afternoon' => 'Afternoon (12PM-5PM)',
            'evening' => 'Evening (5PM-8PM)',
            default => ucfirst($this->delivery_time ?? 'Not set')
        };
    }

    /**
     * Record a delivery.
     */
    public function recordDelivery(Carbon $date): void
    {
        $this->increment('total_deliveries');
        $this->recalculateNextDelivery();
    }

    /**
     * Check if subscription is in grace period
     */
    public function isInGracePeriod(): bool
    {
        return $this->grace_period_ends_at !== null && $this->grace_period_ends_at->isFuture();
    }

    /**
     * Check if max retry attempts have been reached
     */
    public function hasExceededMaxRetries(): bool
    {
        $maxRetries = (int) config('services.subscription.max_retry_attempts', 3);
        return $this->failed_payment_count >= $maxRetries;
    }

    /**
     * Check if subscription is ready for retry
     */
    public function isReadyForRetry(): bool
    {
        if ($this->next_retry_at === null) {
            return false;
        }
        
        return $this->next_retry_at->isPast();
    }

    /**
     * Calculate next retry delay in days based on attempt count
     */
    public function getNextRetryDelay(): int
    {
        $delays = config('services.subscription.retry_delays', [2, 4, 6]);
        $attemptIndex = min($this->failed_payment_count, count($delays) - 1);
        
        return $delays[$attemptIndex] ?? 7;
    }

    /**
     * Record failed payment attempt
     */
    public function recordFailedPayment(string $error): void
    {
        $this->increment('failed_payment_count');
        
        $maxRetries = config('services.subscription.max_retry_attempts', 3);
        $newStatus = $this->failed_payment_count >= $maxRetries ? 'on-hold' : $this->status;
        
        $this->update([
            'last_payment_attempt_at' => now(),
            'last_payment_error' => $error,
            'next_retry_at' => now()->addDays($this->getNextRetryDelay()),
            'grace_period_ends_at' => $this->grace_period_ends_at ?? now()->addDays(config('services.subscription.grace_period_days', 7)),
            'status' => $newStatus,
        ]);
    }

    /**
     * Reset retry tracking after successful payment
     */
    public function resetRetryTracking(): void
    {
        $this->update([
            'failed_payment_count' => 0,
            'last_payment_attempt_at' => now(),
            'next_retry_at' => null,
            'last_payment_error' => null,
            'grace_period_ends_at' => null,
            'status' => 'active',
        ]);
    }

    /**
     * Scope to get subscriptions ready for retry
     */
    public function scopeReadyForRetry($query)
    {
        return $query->whereNotNull('next_retry_at')
            ->where('next_retry_at', '<=', now())
            ->whereNull('canceled_at')
            ->where('failed_payment_count', '<', config('services.subscription.max_retry_attempts', 3));
    }

    /**
     * Scope to get subscriptions in grace period
     */
    public function scopeInGracePeriod($query)
    {
        return $query->whereNotNull('grace_period_ends_at')
            ->where('grace_period_ends_at', '>', now())
            ->whereNull('canceled_at');
    }

    /**
     * Scope to get subscriptions with expired grace period
     */
    public function scopeGracePeriodExpired($query)
    {
        return $query->whereNotNull('grace_period_ends_at')
            ->where('grace_period_ends_at', '<=', now())
            ->whereNull('canceled_at');
    }

    /**
     * Get WordPress customer name and email for migrated subscriptions
     */
    public function getWordPressCustomer()
    {
        if (!$this->wordpress_user_id) {
            return null;
        }

        try {
            $wpDb = DB::connection('wordpress');
            
            // Don't add prefix - it's already set in the connection config
            $user = $wpDb->table('users')
                ->where('ID', $this->wordpress_user_id)
                ->first(['display_name', 'user_email']);
            
            if ($user) {
                // Get billing name from user meta if available
                $billingFirstName = $wpDb->table('usermeta')
                    ->where('user_id', $this->wordpress_user_id)
                    ->where('meta_key', 'billing_first_name')
                    ->value('meta_value');
                
                $billingLastName = $wpDb->table('usermeta')
                    ->where('user_id', $this->wordpress_user_id)
                    ->where('meta_key', 'billing_last_name')
                    ->value('meta_value');
                
                $name = trim(($billingFirstName ?? '') . ' ' . ($billingLastName ?? ''));
                
                return (object)[
                    'name' => $name ?: $user->display_name,
                    'email' => $user->user_email,
                ];
            }
        } catch (\Exception $e) {
            Log::error('Failed to get WordPress customer', [
                'subscription_id' => $this->id,
                'wordpress_user_id' => $this->wordpress_user_id,
                'error' => $e->getMessage()
            ]);
        }

        return null;
    }

    /**
     * Get customer name (from subscriber or WordPress)
     */
    public function getCustomerNameAttribute()
    {
        if ($this->subscriber) {
            return $this->subscriber->name;
        }
        
        $wpCustomer = $this->getWordPressCustomer();
        return $wpCustomer ? $wpCustomer->name : 'N/A';
    }

    /**
     * Get customer email (from subscriber or WordPress)
     */
    public function getCustomerEmailAttribute()
    {
        if ($this->subscriber) {
            return $this->subscriber->email;
        }
        
        $wpCustomer = $this->getWordPressCustomer();
        return $wpCustomer ? $wpCustomer->email : 'N/A';
    }
}
