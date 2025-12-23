<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class UKPlantingCalendar extends Model
{
    protected $table = 'uk_planting_calendar';
    
    protected $fillable = [
        'crop_name',
        'crop_family',
        'variety_type',
        'indoor_seed_months',
        'outdoor_seed_months',
        'transplant_months',
        'harvest_months',
        'frost_hardy',
        'uk_hardiness_zone',
        'typical_last_frost',
        'typical_first_frost',
        'uk_region',
        'seasonal_notes',
        'uk_specific_advice',
        'needs_cloche',
        'needs_fleece',
        'needs_polytunnel',
        'source',
        'confidence_score',
    ];
    
    protected $casts = [
        'frost_hardy' => 'boolean',
        'needs_cloche' => 'boolean',
        'needs_fleece' => 'boolean',
        'needs_polytunnel' => 'boolean',
        'typical_last_frost' => 'date',
        'typical_first_frost' => 'date',
        'confidence_score' => 'integer',
    ];
    
    /**
     * Get planting calendar for a specific crop
     */
    public static function getTimingFor(string $cropName, string $varietyType = null, string $season = null)
    {
        $query = static::where('crop_name', 'LIKE', "%{$cropName}%")
            ->orderBy('confidence_score', 'desc');
        
        if ($varietyType) {
            $query->where('variety_type', 'LIKE', "%{$varietyType}%");
        }
        
        // Season-based filtering
        if ($season) {
            $seasonMap = [
                'spring' => ['spring', 'early'],
                'summer' => ['summer', 'early'],
                'autumn' => ['autumn', 'maincrop'],
                'winter' => ['winter', 'maincrop', 'overwintering'],
            ];
            
            $types = $seasonMap[strtolower($season)] ?? [];
            if (!empty($types)) {
                $query->where(function($q) use ($types) {
                    foreach ($types as $type) {
                        $q->orWhere('variety_type', 'LIKE', "%{$type}%");
                    }
                });
            }
        }
        
        return $query->get();
    }
    
    /**
     * Get current month's recommended activities
     */
    public static function getCurrentMonthActivities(string $cropName = null)
    {
        $currentMonth = Carbon::now()->format('M');
        
        $query = static::where(function($q) use ($currentMonth) {
            $q->where('indoor_seed_months', 'LIKE', "%{$currentMonth}%")
              ->orWhere('outdoor_seed_months', 'LIKE', "%{$currentMonth}%")
              ->orWhere('transplant_months', 'LIKE', "%{$currentMonth}%")
              ->orWhere('harvest_months', 'LIKE', "%{$currentMonth}%");
        });
        
        if ($cropName) {
            $query->where('crop_name', 'LIKE', "%{$cropName}%");
        }
        
        return $query->orderBy('confidence_score', 'desc')->get();
    }
    
    /**
     * Format UK planting calendar for AI prompt
     */
    public static function formatForAI(string $cropName, string $season = null): string
    {
        $entries = static::getTimingFor($cropName, null, $season);
        
        if ($entries->isEmpty()) {
            return '';
        }
        
        $output = "\n\nUK PLANTING CALENDAR for {$cropName}:\n";
        
        foreach ($entries as $entry) {
            $varietyLabel = $entry->variety_type ? " ({$entry->variety_type})" : "";
            $output .= "• {$cropName}{$varietyLabel}:\n";
            
            if ($entry->indoor_seed_months) {
                $output .= "  - Sow indoors: {$entry->indoor_seed_months}\n";
            }
            if ($entry->outdoor_seed_months) {
                $output .= "  - Sow outdoors: {$entry->outdoor_seed_months}\n";
            }
            if ($entry->transplant_months) {
                $output .= "  - Transplant: {$entry->transplant_months}\n";
            }
            if ($entry->harvest_months) {
                $output .= "  - Harvest: {$entry->harvest_months}\n";
            }
            
            if ($entry->frost_hardy) {
                $output .= "  - Frost hardy: Yes ({$entry->uk_hardiness_zone})\n";
            }
            
            if ($entry->seasonal_notes) {
                $output .= "  - Timing: {$entry->seasonal_notes}\n";
            }
            
            if ($entry->uk_specific_advice) {
                $output .= "  - UK advice: {$entry->uk_specific_advice}\n";
            }
            
            $protection = [];
            if ($entry->needs_cloche) $protection[] = 'cloche';
            if ($entry->needs_fleece) $protection[] = 'fleece';
            if ($entry->needs_polytunnel) $protection[] = 'polytunnel';
            if (!empty($protection)) {
                $output .= "  - Protection needed: " . implode(', ', $protection) . "\n";
            }
            
            $output .= "\n";
        }
        
        return $output;
    }
    
    /**
     * Check if it's a good time to sow/transplant
     */
    public static function isGoodTimingFor(string $cropName, string $activity = 'sow', Carbon $date = null): bool
    {
        $date = $date ?? Carbon::now();
        $month = $date->format('M');
        
        $entries = static::where('crop_name', 'LIKE', "%{$cropName}%")->get();
        
        foreach ($entries as $entry) {
            $checkField = match($activity) {
                'sow' => $entry->indoor_seed_months ?? $entry->outdoor_seed_months,
                'transplant' => $entry->transplant_months,
                'harvest' => $entry->harvest_months,
                default => null
            };
            
            if ($checkField && str_contains($checkField, $month)) {
                return true;
            }
        }
        
        return false;
    }
}
