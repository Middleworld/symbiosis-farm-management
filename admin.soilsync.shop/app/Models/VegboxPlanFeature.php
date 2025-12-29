<?php

namespace App\Models;

use Laravelcm\Subscriptions\Models\Feature as BaseFeature;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VegboxPlanFeature extends BaseFeature
{
    protected $table = 'vegbox_plan_features';

    protected $fillable = [
        'plan_id',
        'name',
        'slug',
        'description',
        'value',
        'resettable_period',
        'resettable_interval',
        'sort_order',
    ];

    protected $casts = [
        'name' => 'json',
        'description' => 'json',
        'resettable_period' => 'integer',
        'sort_order' => 'integer',
    ];

    /**
     * Get the plan that owns this feature.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(VegboxPlan::class, 'plan_id');
    }
}
