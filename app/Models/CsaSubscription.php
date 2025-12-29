<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CsaSubscription extends Model
{
    use SoftDeletes;

    protected $table = 'csa_subscriptions';

    protected $fillable = [
        'customer_id',
        'customer_email',
        'customer_name',
        'product_id',
        'product_variation_id',
        'woo_subscription_id',
        'woo_product_id',
        'payment_schedule',
        'delivery_frequency',
        'box_size',
        'fulfillment_type',
        'price',
        'season_total',
        'delivery_address',
        'delivery_postcode',
        'delivery_day',
        'delivery_time',
        'fortnightly_week',
        'season_start_date',
        'season_end_date',
        'next_billing_date',
        'next_delivery_date',
        'deliveries_remaining',
        'status',
        'status_notes',
        'is_paused',
        'paused_until',
        'skipped_dates',
        'failed_payment_count',
        'last_payment_date',
        'grace_period_ends_at',
        'metadata',
        'imported_from_woo',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'season_total' => 'decimal:2',
        'season_start_date' => 'date',
        'season_end_date' => 'date',
        'next_billing_date' => 'date',
        'next_delivery_date' => 'date',
        'is_paused' => 'boolean',
        'paused_until' => 'date',
        'skipped_dates' => 'array',
        'last_payment_date' => 'datetime',
        'grace_period_ends_at' => 'datetime',
        'metadata' => 'array',
        'imported_from_woo' => 'boolean',
        'deliveries_remaining' => 'integer',
        'failed_payment_count' => 'integer',
    ];

    /**
     * Get the deliveries for this subscription.
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(CsaDelivery::class, 'subscription_id');
    }

    /**
     * Get the product for this subscription.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Get the product variation for this subscription.
     */
    public function variation(): BelongsTo
    {
        return $this->belongsTo(ProductVariation::class, 'product_variation_id');
    }

    /**
     * Scope to get active subscriptions.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get subscriptions by fulfillment type.
     */
    public function scopeByFulfillmentType($query, string $type)
    {
        return $query->where('fulfillment_type', $type);
    }

    /**
     * Scope to get deliveries (not collections).
     */
    public function scopeDeliveries($query)
    {
        return $query->where('fulfillment_type', 'Delivery');
    }

    /**
     * Scope to get collections (not deliveries).
     */
    public function scopeCollections($query)
    {
        return $query->where('fulfillment_type', 'Collection');
    }

    /**
     * Scope to get subscriptions by delivery day.
     */
    public function scopeByDeliveryDay($query, string $day)
    {
        return $query->where('delivery_day', $day);
    }

    /**
     * Scope to get subscriptions by fortnightly week.
     */
    public function scopeByFortnightlyWeek($query, string $week)
    {
        return $query->where('fortnightly_week', $week);
    }

    /**
     * Scope to get weekly subscriptions.
     */
    public function scopeWeekly($query)
    {
        return $query->where('delivery_frequency', 'Weekly');
    }

    /**
     * Scope to get fortnightly subscriptions.
     */
    public function scopeFortnightly($query)
    {
        return $query->where('delivery_frequency', 'Fortnightly');
    }

    /**
     * Check if subscription is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && !$this->is_paused;
    }

    /**
     * Check if subscription is paused.
     */
    public function isPaused(): bool
    {
        return $this->is_paused || ($this->paused_until && $this->paused_until->isFuture());
    }

    /**
     * Check if a specific date is skipped.
     */
    public function isDateSkipped($date): bool
    {
        $dateString = is_string($date) ? $date : $date->format('Y-m-d');
        return in_array($dateString, $this->skipped_dates ?? []);
    }

    /**
     * Skip a delivery date.
     */
    public function skipDate($date): void
    {
        $dateString = is_string($date) ? $date : $date->format('Y-m-d');
        $skipped = $this->skipped_dates ?? [];
        
        if (!in_array($dateString, $skipped)) {
            $skipped[] = $dateString;
            $this->skipped_dates = $skipped;
            $this->save();
        }
    }

    /**
     * Unskip a delivery date.
     */
    public function unskipDate($date): void
    {
        $dateString = is_string($date) ? $date : $date->format('Y-m-d');
        $skipped = $this->skipped_dates ?? [];
        
        if (($key = array_search($dateString, $skipped)) !== false) {
            unset($skipped[$key]);
            $this->skipped_dates = array_values($skipped);
            $this->save();
        }
    }

    /**
     * Pause subscription until a specific date.
     */
    public function pauseUntil($date): void
    {
        $this->is_paused = true;
        $this->paused_until = $date;
        $this->save();
    }

    /**
     * Resume a paused subscription.
     */
    public function resume(): void
    {
        $this->is_paused = false;
        $this->paused_until = null;
        $this->save();
    }

    /**
     * Cancel the subscription.
     */
    public function cancel($reason = null): void
    {
        $this->status = 'cancelled';
        if ($reason) {
            $this->status_notes = $reason;
        }
        $this->save();
    }

    /**
     * Get a human-readable status label.
     */
    public function getStatusLabelAttribute(): string
    {
        $labels = [
            'active' => 'Active',
            'on-hold' => 'On Hold',
            'pending' => 'Pending',
            'cancelled' => 'Cancelled',
            'expired' => 'Expired',
            'pending-cancel' => 'Pending Cancellation',
        ];

        return $labels[$this->status] ?? ucfirst($this->status);
    }

    /**
     * Accessor for next_billing_at (alias for next_billing_date for view compatibility)
     */
    public function getNextBillingAtAttribute()
    {
        return $this->next_billing_date;
    }

    /**
     * Check if subscription is in grace period (has failed payments)
     */
    public function isInGracePeriod(): bool
    {
        return $this->grace_period_ends_at && $this->grace_period_ends_at->isFuture();
    }

    /**
     * Get the subscription plan name
     */
    public function getNameAttribute(): string
    {
        return $this->box_size ?? 'Vegbox Subscription';
    }

    /**
     * Scope to get cancelled subscriptions
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Alias for canceled_at to match view expectations
     */
    public function getCanceledAtAttribute()
    {
        return $this->status === 'cancelled' ? $this->updated_at : null;
    }

    /**
     * Alias for ends_at to match view expectations
     */
    public function getEndsAtAttribute()
    {
        return $this->season_end_date;
    }

    /**
     * Get a human-readable fulfillment label with emoji.
     */
    public function getFulfillmentLabelAttribute(): string
    {
        return $this->fulfillment_type === 'Delivery' ? '🚚 Delivery' : '📦 Collection';
    }

    /**
     * Accessor for starts_at (alias for season_start_date for view compatibility)
     */
    public function getStartsAtAttribute()
    {
        return $this->season_start_date;
    }

    /**
     * Accessor for billing_period (derived from delivery_frequency)
     */
    public function getBillingPeriodAttribute(): string
    {
        return $this->delivery_frequency === 'Fortnightly' ? '2 weeks' : 'week';
    }

    /**
     * Accessor for billing_frequency
     */
    public function getBillingFrequencyAttribute(): int
    {
        return 1;
    }

    /**
     * Accessor for a "plan" object for view compatibility
     * Returns an anonymous object with expected properties
     */
    public function getPlanAttribute()
    {
        return (object) [
            'name' => $this->box_size ?? 'Vegbox Subscription',
            'box_size' => $this->box_size,
            'box_size_display' => $this->box_size,
            'delivery_frequency' => $this->delivery_frequency,
            'delivery_frequency_display' => $this->delivery_frequency,
            'contents_description' => null,
            'price' => $this->price,
        ];
    }

    /**
     * Accessor for a "subscriber" object for view compatibility
     * Returns an anonymous object with expected properties
     */
    public function getSubscriberAttribute()
    {
        return (object) [
            'name' => $this->customer_name,
            'email' => $this->customer_email,
            'id' => $this->customer_id,
        ];
    }

    /**
     * Calculate next delivery date based on frequency.
     */
    public function calculateNextDeliveryDate()
    {
        if (!$this->next_delivery_date) {
            return null;
        }

        $date = $this->next_delivery_date->copy();
        
        if ($this->delivery_frequency === 'Weekly') {
            return $date->addWeek();
        } elseif ($this->delivery_frequency === 'Fortnightly') {
            return $date->addWeeks(2);
        }

        return null;
    }

    /**
     * Accessor for subscriber_id (alias for customer_id for VegboxPaymentService compatibility)
     * Only returns value for native subscriptions (NOT imported from WooCommerce)
     */
    public function getSubscriberIdAttribute()
    {
        // If imported from WooCommerce, don't return subscriber_id
        // (these are handled differently - payment already processed in WooCommerce)
        return $this->woo_subscription_id ? null : $this->customer_id;
    }

    /**
     * Accessor for wordpress_user_id (for VegboxPaymentService compatibility)
     * Returns customer_id if this is a WooCommerce subscription (has woo_subscription_id)
     */
    public function getWordpressUserIdAttribute()
    {
        // If we have a woo_subscription_id, this is a WooCommerce import
        // Return the customer_id as the WordPress user ID
        return $this->woo_subscription_id ? $this->customer_id : null;
    }

    /**
     * Record a failed payment attempt
     */
    public function recordFailedPayment(string $error): void
    {
        $this->failed_payment_count = ($this->failed_payment_count ?? 0) + 1;
        $this->status_notes = $error;
        $this->save();
    }

    /**
     * Reset retry tracking after successful payment
     */
    public function resetRetryTracking(): void
    {
        $this->failed_payment_count = 0;
        $this->status_notes = null;
        $this->grace_period_ends_at = null;
        $this->save();
    }
}
