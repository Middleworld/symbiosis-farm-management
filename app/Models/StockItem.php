<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class StockItem extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'sku',
        'crop_type',
        'current_stock',
        'reserved_stock',
        'available_stock',
        'units',
        'unit_price',
        'minimum_stock',
        'storage_location',
        'last_harvest_date',
        'is_active',
        'track_stock',
        'woocommerce_product_id',
        'last_woo_sync_at',
        'auto_sync_to_woo',
        'description',
        'metadata'
    ];

    protected $casts = [
        'current_stock' => 'decimal:3',
        'reserved_stock' => 'decimal:3',
        'available_stock' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'minimum_stock' => 'decimal:3',
        'last_harvest_date' => 'date',
        'last_woo_sync_at' => 'datetime',
        'is_active' => 'boolean',
        'track_stock' => 'boolean',
        'auto_sync_to_woo' => 'boolean',
        'metadata' => 'array'
    ];

    /**
     * Generate slug when creating
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name);
            }
        });

        static::updating(function ($model) {
            if ($model->isDirty('name') && empty($model->slug)) {
                $model->slug = Str::slug($model->name);
            }
        });
    }

    /**
     * Get harvest logs for this stock item
     */
    public function harvestLogs(): HasMany
    {
        return $this->hasMany(HarvestLog::class, 'crop_name', 'name');
    }

    /**
     * Get recent harvest logs
     */
    public function recentHarvests($days = 30)
    {
        return $this->harvestLogs()
                   ->where('harvest_date', '>=', now()->subDays($days))
                   ->orderBy('harvest_date', 'desc');
    }

    /**
     * Scope for active items
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for low stock items
     */
    public function scopeLowStock($query)
    {
        return $query->whereColumn('available_stock', '<=', 'minimum_stock')
                    ->where('track_stock', true);
    }

    /**
     * Scope by crop type
     */
    public function scopeByCropType($query, $cropType)
    {
        return $query->where('crop_type', $cropType);
    }

    /**
     * Add stock from harvest
     */
    public function addHarvestStock($quantity, $harvestDate = null)
    {
        $this->increment('current_stock', $quantity);
        $this->updateAvailableStock();
        
        if ($harvestDate) {
            $this->update(['last_harvest_date' => $harvestDate]);
        }
    }

    /**
     * Reserve stock for orders
     */
    public function reserveStock($quantity)
    {
        if ($this->available_stock >= $quantity) {
            $this->increment('reserved_stock', $quantity);
            $this->updateAvailableStock();
            return true;
        }
        return false;
    }

    /**
     * Release reserved stock
     */
    public function releaseStock($quantity)
    {
        $this->decrement('reserved_stock', $quantity);
        $this->updateAvailableStock();
    }

    /**
     * Fulfill order (remove from current and reserved stock)
     */
    public function fulfillOrder($quantity)
    {
        $this->decrement('current_stock', $quantity);
        $this->decrement('reserved_stock', $quantity);
        $this->updateAvailableStock();
    }

    /**
     * Update available stock calculation
     */
    public function updateAvailableStock()
    {
        $this->update([
            'available_stock' => $this->current_stock - $this->reserved_stock
        ]);
    }

    /**
     * Check if item is low stock
     */
    public function getIsLowStockAttribute()
    {
        return $this->track_stock && $this->available_stock <= $this->minimum_stock;
    }

    /**
     * Get formatted current stock
     */
    public function getFormattedCurrentStockAttribute()
    {
        return number_format($this->current_stock, 2) . ' ' . $this->units;
    }

    /**
     * Get formatted available stock
     */
    public function getFormattedAvailableStockAttribute()
    {
        return number_format($this->available_stock, 2) . ' ' . $this->units;
    }

    /**
     * Get stock status for display
     */
    public function getStockStatusAttribute()
    {
        if (!$this->track_stock) return 'not-tracked';
        if ($this->is_low_stock) return 'low';
        if ($this->available_stock > $this->minimum_stock * 2) return 'good';
        return 'medium';
    }
}
