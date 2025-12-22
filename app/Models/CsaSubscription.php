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
     * Get a human-readable fulfillment label with emoji.
     */
    public function getFulfillmentLabelAttribute(): string
    {
        return $this->fulfillment_type === 'Delivery' ? '🚚 Delivery' : '📦 Collection';
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
}
