<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class HarvestLog extends Model
{
    protected $fillable = [
        'farmos_id',
        'farmos_asset_id',
        'crop_name',
        'crop_type',
        'quantity',
        'units',
        'measure',
        'harvest_date',
        'location',
        'notes',
        'status',
        'synced_to_stock',
        'farmos_data'
    ];

    protected $casts = [
        'harvest_date' => 'datetime',
        'quantity' => 'decimal:3',
        'synced_to_stock' => 'boolean',
        'farmos_data' => 'array'
    ];

    /**
     * Get the related stock item for this harvest
     */
    public function stockItem(): BelongsTo
    {
        return $this->belongsTo(StockItem::class, 'crop_name', 'name');
    }

    /**
     * Get the related crop plan if exists
     */
    public function cropPlan(): BelongsTo
    {
        return $this->belongsTo(CropPlan::class, 'farmos_asset_id', 'farmos_asset_id');
    }

    /**
     * Scope for unsynced harvest logs
     */
    public function scopeUnsynced($query)
    {
        return $query->where('synced_to_stock', false);
    }

    /**
     * Scope for recent harvests
     */
    public function scopeRecent($query, $days = 7)
    {
        return $query->where('harvest_date', '>=', Carbon::now()->subDays($days));
    }

    /**
     * Scope by crop type
     */
    public function scopeByCropType($query, $cropType)
    {
        return $query->where('crop_type', $cropType);
    }

    /**
     * Mark this harvest as synced to stock
     */
    public function markAsSynced()
    {
        $this->update(['synced_to_stock' => true]);
    }

    /**
     * Get formatted quantity with units
     */
    public function getFormattedQuantityAttribute()
    {
        return number_format($this->quantity, 2) . ' ' . $this->units;
    }

    /**
     * Check if this harvest is from today
     */
    public function getIsTodayAttribute()
    {
        return $this->harvest_date->isToday();
    }
}
