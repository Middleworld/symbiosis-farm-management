<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingClass extends Model
{
    protected $fillable = [
        'woo_id',
        'name',
        'slug',
        'description',
        'cost',
        'is_free',
        'is_farm_collection',
        'delivery_zones',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'cost' => 'decimal:2',
        'is_free' => 'boolean',
        'is_farm_collection' => 'boolean',
        'delivery_zones' => 'array',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the products that belong to this shipping class.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Scope to get only active shipping classes.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get only farm collection shipping classes.
     */
    public function scopeFarmCollection($query)
    {
        return $query->where('is_farm_collection', true);
    }

    /**
     * Scope to get only paid delivery shipping classes.
     */
    public function scopePaidDelivery($query)
    {
        return $query->where('is_farm_collection', false);
    }
}
