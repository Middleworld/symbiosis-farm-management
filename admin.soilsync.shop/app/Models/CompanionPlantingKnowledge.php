<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanionPlantingKnowledge extends Model
{
    protected $table = 'companion_planting_knowledge';
    
    protected $fillable = [
        'primary_crop',
        'primary_crop_family',
        'companion_plant',
        'companion_family',
        'relationship_type',
        'benefits',
        'planting_notes',
        'planting_timing',
        'spacing_notes',
        'intercrop_type',
        'days_to_harvest_companion',
        'quick_crop',
        'seasonal_considerations',
        'source',
        'confidence_score',
    ];
    
    protected $casts = [
        'quick_crop' => 'boolean',
        'days_to_harvest_companion' => 'integer',
        'confidence_score' => 'integer',
    ];
    
    /**
     * Get companion suggestions for a specific crop
     */
    public static function getCompanionsFor(string $cropName, string $cropFamily = null, string $season = null)
    {
        $query = static::where('relationship_type', 'beneficial')
            ->where(function ($q) use ($cropName, $cropFamily) {
                $q->where('primary_crop', 'LIKE', "%{$cropName}%");
                if ($cropFamily) {
                    $q->orWhere('primary_crop_family', $cropFamily);
                }
            })
            ->orderBy('confidence_score', 'desc')
            ->orderBy('quick_crop', 'desc'); // Quick crops first
            
        return $query->get();
    }
    
    /**
     * Get quick intercrop suggestions
     */
    public static function getQuickIntercrops(string $cropName, string $cropFamily = null)
    {
        return static::where('relationship_type', 'beneficial')
            ->where('quick_crop', true)
            ->where(function ($q) use ($cropName, $cropFamily) {
                $q->where('primary_crop', 'LIKE', "%{$cropName}%");
                if ($cropFamily) {
                    $q->orWhere('primary_crop_family', $cropFamily);
                }
            })
            ->orderBy('days_to_harvest_companion', 'asc')
            ->get();
    }
    
    /**
     * Get underplanting suggestions
     */
    public static function getUnderplantingOptions(string $cropName, string $cropFamily = null)
    {
        return static::where('relationship_type', 'beneficial')
            ->where('intercrop_type', 'underplant')
            ->where(function ($q) use ($cropName, $cropFamily) {
                $q->where('primary_crop', 'LIKE', "%{$cropName}%");
                if ($cropFamily) {
                    $q->orWhere('primary_crop_family', $cropFamily);
                }
            })
            ->orderBy('confidence_score', 'desc')
            ->get();
    }
    
    /**
     * Format companion knowledge for AI prompt
     */
    public static function formatForAI(string $cropName, string $cropFamily = null): string
    {
        $companions = static::getCompanionsFor($cropName, $cropFamily);
        
        if ($companions->isEmpty()) {
            return '';
        }
        
        $output = "\n\nCOMPANION PLANTING KNOWLEDGE for {$cropName}:\n";
        
        foreach ($companions as $comp) {
            $output .= "• {$comp->companion_plant}";
            if ($comp->intercrop_type) {
                $output .= " ({$comp->intercrop_type})";
            }
            $output .= ": {$comp->benefits}";
            if ($comp->planting_timing) {
                $output .= " Timing: {$comp->planting_timing}.";
            }
            if ($comp->seasonal_considerations) {
                $output .= " Note: {$comp->seasonal_considerations}";
            }
            $output .= "\n";
        }
        
        return $output;
    }
}
