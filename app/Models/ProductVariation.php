<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariation extends Model
{
    protected $fillable = [
        'woo_id',
        'woo_variation_id',
        'product_id',
        'sku',
        'name',
        'description',
        'price',
        'regular_price',
        'sale_price',
        'attributes',
        'image_url',
        'is_active',
        'stock_quantity',
        'stock_status',
        'manage_stock',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'regular_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'attributes' => 'array',
        'is_active' => 'boolean',
        'stock_quantity' => 'integer',
        'manage_stock' => 'boolean',
        'sort_order' => 'integer',
    ];
    
    /**
     * Boot the model and register events.
     */
    protected static function boot()
    {
        parent::boot();
        
        static::saved(function ($variation) {
            // Trigger parent product sync when variation changes
            if ($variation->product && $variation->product->woo_product_id) {
                try {
                    $wooService = app(\App\Services\WooCommerceApiService::class);
                    $wooService->syncProduct($variation->product);
                } catch (\Exception $e) {
                    \Log::error("Failed to auto-sync product after variation update: " . $e->getMessage());
                }
            }
        });
    }

    /**
     * Get the product that owns this variation.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Scope to get only active variations.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get variations for a specific product.
     */
    public function scopeForProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
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
