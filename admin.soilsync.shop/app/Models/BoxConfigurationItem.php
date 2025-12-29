<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BoxConfigurationItem extends Model
{
    protected $fillable = [
        'box_configuration_id',
        'plant_variety_id',
        'product_id',
        'item_name',
        'description',
        'token_value',
        'price_at_time',
        'quantity',
        'quantity_available',
        'quantity_allocated',
        'unit',
        'farmos_harvest_id',
        'is_featured',
        'sort_order',
    ];

    protected $casts = [
        'token_value' => 'integer',
        'quantity_available' => 'integer',
        'quantity_allocated' => 'integer',
        'is_featured' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get the box configuration this item belongs to.
     */
    public function boxConfiguration(): BelongsTo
    {
        return $this->belongsTo(BoxConfiguration::class);
    }

    /**
     * Get the plant variety if linked.
     */
    public function plantVariety(): BelongsTo
    {
        return $this->belongsTo(PlantVariety::class);
    }

    /**
     * Get customer box items using this configuration item.
     */
    public function customerBoxItems(): HasMany
    {
        return $this->hasMany(CustomerBoxItem::class);
    }

    /**
     * Check if item is still available.
     */
    public function getIsAvailableAttribute(): bool
    {
        if ($this->quantity_available === null) {
            return true; // Unlimited
        }
        
        return $this->quantity_allocated < $this->quantity_available;
    }

    /**
     * Get remaining quantity.
     */
    public function getRemainingQuantityAttribute(): ?int
    {
        if ($this->quantity_available === null) {
            return null; // Unlimited
        }
        
        return max(0, $this->quantity_available - $this->quantity_allocated);
    }

    /**
     * Get allocation percentage.
     */
    public function getAllocationPercentAttribute(): float
    {
        if ($this->quantity_available === null || $this->quantity_available == 0) {
            return 0;
        }
        
        return round(($this->quantity_allocated / $this->quantity_available) * 100, 1);
    }

    /**
     * Scope for featured items.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope for available items.
     */
    public function scopeAvailable($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('quantity_available')
              ->orWhereRaw('quantity_allocated < quantity_available');
        });
    }

    /**
     * Scope for ordered by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('item_name');
    }
}
