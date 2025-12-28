<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class BoxConfiguration extends Model
{
    protected $fillable = [
        'week_starting',
        'plan_id',
        'is_active',
        'default_tokens',
        'admin_notes',
    ];

    protected $casts = [
        'week_starting' => 'date',
        'is_active' => 'boolean',
        'default_tokens' => 'integer',
    ];

    /**
     * Get the vegbox plan for this configuration.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(VegboxPlan::class, 'plan_id');
    }

    /**
     * Get the available items for this box configuration.
     */
    public function items(): HasMany
    {
        return $this->hasMany(BoxConfigurationItem::class);
    }

    /**
     * Get customer selections for this configuration.
     */
    public function customerSelections(): HasMany
    {
        return $this->hasMany(CustomerBoxSelection::class);
    }

    /**
     * Scope for current week's configuration.
     */
    public function scopeCurrentWeek($query)
    {
        $weekStart = Carbon::now()->startOfWeek();
        return $query->where('week_starting', $weekStart);
    }

    /**
     * Scope for upcoming weeks.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('week_starting', '>=', Carbon::now()->startOfWeek())
                     ->orderBy('week_starting');
    }

    /**
     * Scope for active configurations.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get week display string (e.g., "Week of 28 Dec 2025").
     */
    public function getWeekDisplayAttribute(): string
    {
        return 'Week of ' . $this->week_starting->format('d M Y');
    }

    /**
     * Get total quantity allocated vs available.
     */
    public function getAllocationSummary(): array
    {
        $items = $this->items;
        
        return [
            'total_items' => $items->count(),
            'total_available' => $items->sum('quantity_available'),
            'total_allocated' => $items->sum('quantity_allocated'),
            'utilization_percent' => $items->sum('quantity_available') > 0 
                ? round(($items->sum('quantity_allocated') / $items->sum('quantity_available')) * 100, 1)
                : 0,
        ];
    }
}
