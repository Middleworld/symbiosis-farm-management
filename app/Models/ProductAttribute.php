<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductAttribute extends Model
{
    protected $fillable = [
        'woo_id',
        'name',
        'slug',
        'type',
        'is_visible',
        'is_variation',
        'is_taxonomy',
        'options',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
        'is_variation' => 'boolean',
        'is_taxonomy' => 'boolean',
        'options' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get the variations that use this attribute.
     * Note: This is a complex relationship since variations store attributes as JSON
     */
    public function variations()
    {
        // This is a has-many-through relationship via the attributes JSON column
        // For now, we'll return an empty collection and handle this in the controller/view
        return collect([]);
    }

    /**
     * Check if this attribute is used in any product variations
     */
    public function isUsedInVariations(): bool
    {
        // Query variations where this attribute's slug appears in the attributes JSON
        return \DB::table('product_variations')
            ->where('attributes', 'like', '%"' . $this->slug . '"%')
            ->exists();
    }

    /**
     * Get variations that use this attribute
     */
    public function getRelatedVariations()
    {
        return \DB::table('product_variations')
            ->where('attributes', 'like', '%"' . $this->slug . '"%')
            ->get()
            ->map(function ($variation) {
                // Convert to a more usable format
                $variation->attributes = json_decode($variation->attributes, true);
                return $variation;
            });
    }

    /**
     * Scope to get only active attributes.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get only variation attributes.
     */
    public function scopeForVariations($query)
    {
        return $query->where('is_variation', true);
    }

    /**
     * Scope to get only visible attributes.
     */
    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }
}
