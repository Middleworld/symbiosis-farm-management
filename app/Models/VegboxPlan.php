<?php

namespace App\Models;

use Laravelcm\Subscriptions\Models\Plan as BasePlan;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VegboxPlan extends BasePlan
{
    protected $table = 'vegbox_plans';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
        'price',
        'signup_fee',
        'currency',
        'trial_period',
        'trial_interval',
        'invoice_period',
        'invoice_interval',
        'grace_period',
        'grace_interval',
        'prorate_day',
        'prorate_period',
        'prorate_extend_due',
        'active_subscribers_limit',
        'sort_order',
        // Vegbox-specific fields
        'box_size', // small, medium, large
        'delivery_frequency', // weekly, bi-weekly
        'max_deliveries_per_month',
        'contents_description',
    ];

    protected $casts = [
        'name' => 'json',
        'description' => 'json',
        'is_active' => 'boolean',
        'price' => 'decimal:2',
        'signup_fee' => 'decimal:2',
        'trial_period' => 'integer',
        'invoice_period' => 'integer',
        'grace_period' => 'integer',
        'prorate_day' => 'integer',
        'active_subscribers_limit' => 'integer',
        'sort_order' => 'integer',
        'max_deliveries_per_month' => 'integer',
    ];

    /**
     * Get the vegbox subscriptions for this plan.
     */
    public function vegboxSubscriptions(): HasMany
    {
        return $this->hasMany(VegboxSubscription::class, 'plan_id');
    }

    /**
     * Get the features for this plan.
     */
    public function features(): HasMany
    {
        return $this->hasMany(VegboxPlanFeature::class, 'plan_id');
    }

    /**
     * Scope for active vegbox plans.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the box size display name.
     */
    public function getBoxSizeDisplayAttribute(): string
    {
        // Box size is now the actual name, just append " Box" if not already present
        if (str_contains($this->box_size ?? '', 'Box')) {
            return $this->box_size;
        }
        return ($this->box_size ?? 'Unknown') . ' Box';
    }

    /**
     * Get the delivery frequency display name.
     */
    public function getDeliveryFrequencyDisplayAttribute(): string
    {
        return match($this->delivery_frequency) {
            'weekly' => 'Weekly',
            'bi-weekly' => 'Bi-weekly',
            default => ucfirst($this->delivery_frequency ?? 'Unknown')
        };
    }
}
