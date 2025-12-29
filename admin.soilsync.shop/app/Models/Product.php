<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'sku',
        'product_type',
        'description',
        'price',
        'cost_price',
        'category',
        'subcategory',
        'product_categories',
        'product_tags',
        'brand',
        'image_url',
        'local_image_path',
        'gallery_images',
        'barcode',
        'stock_quantity',
        'min_stock_level',
        'max_stock_level',
        'is_active',
        'is_taxable',
        'tax_rate',
        'weight',
        'unit',
        'supplier_id',
        'woo_product_id',
        'reviews_enabled',
        'upsell_ids',
        'crosssell_ids',
        'metadata' // Stores solidarity pricing settings, short_description, and other custom data
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'stock_quantity' => 'integer',
        'min_stock_level' => 'integer',
        'max_stock_level' => 'integer',
        'is_active' => 'boolean',
        'is_taxable' => 'boolean',
        'tax_rate' => 'decimal:2',
        'reviews_enabled' => 'boolean',
        'upsell_ids' => 'array',
        'crosssell_ids' => 'array',
        'weight' => 'decimal:3',
        'metadata' => 'array',
        'gallery_images' => 'array',
        'product_categories' => 'array',
        'product_tags' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    // Relationships
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function shippingClass()
    {
        return $this->belongsTo(ShippingClass::class);
    }

    public function variations()
    {
        return $this->hasMany(ProductVariation::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInStock($query)
    {
        return $query->where('stock_quantity', '>', 0);
    }

    public function scopeLowStock($query)
    {
        return $query->whereColumn('stock_quantity', '<=', 'min_stock_level');
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    // Accessors & Mutators
    public function getFormattedPriceAttribute()
    {
        return '£' . number_format($this->price, 2);
    }

    public function getStockStatusAttribute()
    {
        if ($this->stock_quantity <= 0) {
            return 'out_of_stock';
        } elseif ($this->stock_quantity <= $this->min_stock_level) {
            return 'low_stock';
        }
        return 'in_stock';
    }

    public function getStockStatusColorAttribute()
    {
        return match($this->stock_status) {
            'out_of_stock' => 'red',
            'low_stock' => 'orange',
            'in_stock' => 'green',
            default => 'gray'
        };
    }

    // Methods
    public function adjustStock($quantity, $reason = null)
    {
        $this->stock_quantity += $quantity;
        $this->save();

        // Log stock adjustment
        StockAdjustment::create([
            'product_id' => $this->id,
            'quantity' => $quantity,
            'reason' => $reason,
            'user_id' => auth()->id()
        ]);
    }

    public function isLowStock()
    {
        return $this->stock_quantity <= $this->min_stock_level;
    }

    public function calculateTax($quantity = 1)
    {
        if (!$this->is_taxable) {
            return 0;
        }

        return ($this->price * $quantity) * ($this->tax_rate / 100);
    }

    public function getTotalPrice($quantity = 1, $includeTax = true)
    {
        $subtotal = $this->price * $quantity;

        if ($includeTax && $this->is_taxable) {
            $subtotal += $this->calculateTax($quantity);
        }

        return $subtotal;
    }
}