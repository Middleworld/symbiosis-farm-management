<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WooCommerceProduct extends Model
{
    protected $table = 'woocommerce_products';

    protected $fillable = [
        'woo_id',
        'name',
        'slug',
        'description',
        'type',
        'sku',
        'price',
        'regular_price',
        'sale_price',
        'is_subscription',
        'vegbox_plan_id',
        'billing_period',
        'billing_interval',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'regular_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'is_subscription' => 'boolean',
        'billing_interval' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get the variations for this product.
     */
    public function variations(): HasMany
    {
        return $this->hasMany(ProductVariation::class, 'product_id');
    }

    /**
     * Scope to get only active products.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get only subscription products.
     */
    public function scopeSubscriptions($query)
    {
        return $query->where('is_subscription', true);
    }

    /**
     * Scope to get only variable products.
     */
    public function scopeVariable($query)
    {
        return $query->where('type', 'variable');
    }

    /**
     * Get formatted price for display.
     */
    public function getFormattedPriceAttribute()
    {
        if ($this->sale_price && $this->sale_price < $this->regular_price) {
            return '£' . number_format($this->sale_price, 2);
        }
        
        return $this->price ? '£' . number_format($this->price, 2) : 'Price not set';
    }
}
