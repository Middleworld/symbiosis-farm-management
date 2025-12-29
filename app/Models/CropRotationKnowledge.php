<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CropRotationKnowledge extends Model
{
    protected $table = 'crop_rotation_knowledge';
    
    protected $fillable = [
        'previous_crop',
        'previous_crop_family',
        'following_crop',
        'following_crop_family',
        'relationship',
        'benefits',
        'risks',
        'minimum_gap_months',
        'breaks_disease_cycle',
        'improves_soil_structure',
        'fixes_nitrogen',
        'depletes_nitrogen',
        'soil_consideration',
        'cover_crop_recommendation',
        'source',
        'confidence_score',
    ];
    
    protected $casts = [
        'breaks_disease_cycle' => 'boolean',
        'improves_soil_structure' => 'boolean',
        'fixes_nitrogen' => 'boolean',
        'depletes_nitrogen' => 'boolean',
        'minimum_gap_months' => 'integer',
        'confidence_score' => 'integer',
    ];
    
    /**
     * Get rotation advice for what should follow a crop
     */
    public static function getRotationAdvice(string $previousCrop, string $previousFamily = null)
    {
        $query = static::where(function ($q) use ($previousCrop, $previousFamily) {
            $q->where('previous_crop', 'LIKE', "%{$previousCrop}%");
            if ($previousFamily) {
                $q->orWhere('previous_crop_family', $previousFamily);
            }
        })
        ->whereIn('relationship', ['excellent', 'good'])
        ->orderByRaw("FIELD(relationship, 'excellent', 'good')")
        ->orderBy('confidence_score', 'desc');
        
        return $query->get();
    }
    
    /**
     * Get what to avoid after a crop
     */
    public static function getRotationWarnings(string $previousCrop, string $previousFamily = null)
    {
        $query = static::where(function ($q) use ($previousCrop, $previousFamily) {
            $q->where('previous_crop', 'LIKE', "%{$previousCrop}%");
            if ($previousFamily) {
                $q->orWhere('previous_crop_family', $previousFamily);
            }
        })
        ->whereIn('relationship', ['avoid', 'poor'])
        ->orderBy('confidence_score', 'desc');
        
        return $query->get();
    }
    
    /**
     * Get nitrogen-fixing crops to follow
     */
    public static function getNitrogenFixers(string $previousCrop, string $previousFamily = null)
    {
        return static::where(function ($q) use ($previousCrop, $previousFamily) {
            $q->where('previous_crop', 'LIKE', "%{$previousCrop}%");
            if ($previousFamily) {
                $q->orWhere('previous_crop_family', $previousFamily);
            }
        })
        ->where('fixes_nitrogen', true)
        ->where('relationship', 'excellent')
        ->get();
    }
    
    /**
     * Format rotation knowledge for AI prompt
     */
    public static function formatForAI(string $previousCrop, string $previousFamily = null): string
    {
        $advice = static::getRotationAdvice($previousCrop, $previousFamily);
        $warnings = static::getRotationWarnings($previousCrop, $previousFamily);
        
        if ($advice->isEmpty() && $warnings->isEmpty()) {
            return '';
        }
        
        $output = "\n\nCROP ROTATION ADVICE after {$previousCrop}:\n";
        
        // Good rotations
        if ($advice->isNotEmpty()) {
            $output .= "RECOMMENDED to follow:\n";
            foreach ($advice->take(3) as $rot) {
                $output .= "• {$rot->following_crop} ({$rot->relationship}): {$rot->benefits}";
                if ($rot->soil_consideration) {
                    $output .= " {$rot->soil_consideration}.";
                }
                if ($rot->cover_crop_recommendation) {
                    $output .= " Cover crop option: {$rot->cover_crop_recommendation}.";
                }
                $output .= "\n";
            }
        }
        
        // Avoid rotations
        if ($warnings->isNotEmpty()) {
            $output .= "AVOID:\n";
            foreach ($warnings as $rot) {
                $output .= "• {$rot->following_crop} ({$rot->relationship})";
                if ($rot->risks) {
                    $output .= ": {$rot->risks}";
                }
                if ($rot->minimum_gap_months) {
                    $output .= " Wait at least {$rot->minimum_gap_months} months.";
                }
                $output .= "\n";
            }
        }
        
        return $output;
    }
}
