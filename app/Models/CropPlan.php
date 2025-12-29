<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class CropPlan extends Model
{
    protected $fillable = [
        'farmos_asset_id',
        'crop_name',
        'crop_type',
        'variety',
        'planned_seeding_date',
        'actual_seeding_date',
        'planned_transplant_date',
        'actual_transplant_date',
        'planned_harvest_start',
        'planned_harvest_end',
        'actual_harvest_start',
        'actual_harvest_end',
        'location',
        'planned_quantity',
        'actual_quantity',
        'quantity_units',
        'expected_yield',
        'actual_yield',
        'yield_units',
        'status',
        'notes',
        'farmos_data'
    ];

    protected $casts = [
        'planned_seeding_date' => 'date',
        'actual_seeding_date' => 'date',
        'planned_transplant_date' => 'date',
        'actual_transplant_date' => 'date',
        'planned_harvest_start' => 'date',
        'planned_harvest_end' => 'date',
        'actual_harvest_start' => 'date',
        'actual_harvest_end' => 'date',
        'expected_yield' => 'decimal:3',
        'actual_yield' => 'decimal:3',
        'farmos_data' => 'array'
    ];

    /**
     * Get harvest logs for this crop plan
     */
    public function harvestLogs(): HasMany
    {
        return $this->hasMany(HarvestLog::class, 'farmos_asset_id', 'farmos_asset_id');
    }

    /**
     * Scope for current season crops
     */
    public function scopeCurrentSeason($query)
    {
        $now = Carbon::now();
        return $query->where(function($q) use ($now) {
            $q->where('planned_harvest_start', '<=', $now)
              ->where('planned_harvest_end', '>=', $now);
        });
    }

    /**
     * Scope by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for upcoming harvests
     */
    public function scopeUpcomingHarvest($query, $days = 14)
    {
        $start = Carbon::now();
        $end = Carbon::now()->addDays($days);
        
        return $query->where('planned_harvest_start', '>=', $start)
                    ->where('planned_harvest_start', '<=', $end)
                    ->whereIn('status', ['seeded', 'growing']);
    }

    /**
     * Scope for overdue tasks
     */
    public function scopeOverdue($query)
    {
        $now = Carbon::now();
        return $query->where(function($q) use ($now) {
            $q->where('status', 'planned')
              ->where('planned_seeding_date', '<', $now)
              ->orWhere(function($q2) use ($now) {
                  $q2->where('status', 'seeded')
                     ->where('planned_transplant_date', '<', $now);
              });
        });
    }

    /**
     * Calculate yield efficiency
     */
    public function getYieldEfficiencyAttribute()
    {
        if ($this->expected_yield > 0) {
            return round(($this->actual_yield / $this->expected_yield) * 100, 1);
        }
        return 0;
    }

    /**
     * Get days until planned harvest
     */
    public function getDaysUntilHarvestAttribute()
    {
        if ($this->planned_harvest_start) {
            return Carbon::now()->diffInDays($this->planned_harvest_start, false);
        }
        return null;
    }

    /**
     * Check if harvest is ready
     */
    public function getIsReadyForHarvestAttribute()
    {
        return $this->planned_harvest_start && 
               $this->planned_harvest_start <= Carbon::now() &&
               in_array($this->status, ['growing', 'seeded']);
    }

    /**
     * Get status badge class for UI
     */
    public function getStatusBadgeClassAttribute()
    {
        return match($this->status) {
            'planned' => 'secondary',
            'seeded' => 'info',
            'growing' => 'success',
            'harvesting' => 'warning',
            'completed' => 'primary',
            default => 'light'
        };
    }

    /**
     * Update status based on dates
     */
    public function updateStatusFromDates()
    {
        $now = Carbon::now();
        
        if ($this->actual_harvest_end && $now >= $this->actual_harvest_end) {
            $this->status = 'completed';
        } elseif ($this->actual_harvest_start && $now >= $this->actual_harvest_start) {
            $this->status = 'harvesting';
        } elseif ($this->actual_transplant_date && $now >= $this->actual_transplant_date) {
            $this->status = 'growing';
        } elseif ($this->actual_seeding_date && $now >= $this->actual_seeding_date) {
            $this->status = 'seeded';
        }
        
        $this->save();
    }

    /**
     * Add harvest to this plan
     */
    public function addHarvest($quantity, $harvestDate = null)
    {
        $this->increment('actual_yield', $quantity);
        
        if (!$this->actual_harvest_start || ($harvestDate && $harvestDate < $this->actual_harvest_start)) {
            $this->actual_harvest_start = $harvestDate ?? Carbon::now();
        }
        
        $this->actual_harvest_end = $harvestDate ?? Carbon::now();
        $this->save();
        
        $this->updateStatusFromDates();
    }

    /**
     * Calculate progress percentage based on current status and dates
     */
    public function calculateProgress()
    {
        $progress = 0;
        
        switch ($this->status) {
            case 'planned':
                $progress = 10;
                break;
            case 'seeded':
                $progress = 25;
                break;
            case 'transplanted':
                $progress = 50;
                break;
            case 'growing':
                $progress = 75;
                break;
            case 'ready_to_harvest':
                $progress = 90;
                break;
            case 'completed':
                $progress = 100;
                break;
            case 'cancelled':
                $progress = 0;
                break;
        }
        
        // Adjust based on actual dates vs planned dates
        if ($this->actual_seed_date && $this->planned_seed_date) {
            $progress = max($progress, 25);
        }
        
        if ($this->actual_transplant_date && $this->planned_transplant_date) {
            $progress = max($progress, 50);
        }
        
        if ($this->actual_harvest_date && $this->expected_harvest_date) {
            $progress = 100;
        }
        
        return $progress;
    }
}
