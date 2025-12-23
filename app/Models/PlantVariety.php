<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlantVariety extends Model
{
    protected $fillable = [
        'farmos_id',
        'farmos_tid',
        'name',
        'description',
        'scientific_name',
        'crop_family',
        'plant_type',
        'plant_type_id',
        'maturity_days',
        'transplant_days',
        'harvest_days',
        'min_temperature',
        'max_temperature',
        'optimal_temperature',
        'season',
        'frost_tolerance',
        'companions',
        'external_uris',
        'farmos_data',
        'is_active',
        'last_synced_at',
        'sync_status',
        // New spacing fields
        'in_row_spacing_cm',
        'between_row_spacing_cm',
        'planting_method',
    ];

    protected $casts = [
        'companions' => 'array',
        'external_uris' => 'array',
        'farmos_data' => 'array',
        'is_active' => 'boolean',
        'last_synced_at' => 'datetime',
    ];
}
