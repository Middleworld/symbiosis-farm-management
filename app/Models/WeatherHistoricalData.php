<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WeatherHistoricalData extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'latitude',
        'longitude',
        'year',
        'month',
        'day',
        'temperature_max',
        'temperature_min',
        'temperature_avg',
        'humidity',
        'pressure',
        'wind_speed',
        'wind_direction',
        'precipitation',
        'snowfall',
        'cloudiness',
        'weather_condition',
        'weather_description',
        'uv_index',
        'sunrise',
        'sunset',
        'growing_degree_days',
        'frost_risk',
        'planting_suitability',
        'raw_data'
    ];

    protected $casts = [
        'date' => 'date',
        'sunrise' => 'datetime',
        'sunset' => 'datetime',
        'raw_data' => 'array',
        'temperature_max' => 'decimal:2',
        'temperature_min' => 'decimal:2',
        'temperature_avg' => 'decimal:2',
        'humidity' => 'integer',
        'pressure' => 'decimal:2',
        'wind_speed' => 'decimal:2',
        'wind_direction' => 'integer',
        'precipitation' => 'decimal:2',
        'snowfall' => 'decimal:2',
        'cloudiness' => 'integer',
        'uv_index' => 'decimal:2',
        'growing_degree_days' => 'decimal:2'
    ];

    /**
     * Get weather data for a specific date range
     */
    public static function getDateRange($startDate, $endDate)
    {
        return self::whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->get();
    }

    /**
     * Get weather patterns for a specific month across years
     */
    public static function getMonthlyPatterns($month, $startYear = null, $endYear = null)
    {
        $query = self::where('month', $month);

        if ($startYear && $endYear) {
            $query->whereBetween('year', [$startYear, $endYear]);
        }

        return $query->selectRaw('
                year,
                AVG(temperature_avg) as avg_temperature,
                MIN(temperature_min) as min_temperature,
                MAX(temperature_max) as max_temperature,
                AVG(precipitation) as avg_precipitation,
                SUM(precipitation) as total_precipitation,
                COUNT(CASE WHEN frost_risk != "no_frost_risk" THEN 1 END) as frost_days,
                COUNT(CASE WHEN planting_suitability = "excellent" THEN 1 END) as excellent_planting_days
            ')
            ->groupBy('year')
            ->orderBy('year')
            ->get();
    }

    /**
     * Get growing degree days accumulation for a period
     */
    public static function getGddAccumulation($startDate, $endDate, $baseTemp = 10)
    {
        return self::whereBetween('date', [$startDate, $endDate])
            ->selectRaw('
                SUM(growing_degree_days) as total_gdd,
                AVG(growing_degree_days) as avg_daily_gdd,
                MAX(growing_degree_days) as max_daily_gdd,
                COUNT(*) as days_count
            ')
            ->first();
    }

    /**
     * Get frost risk analysis for a period
     */
    public static function getFrostAnalysis($startDate, $endDate)
    {
        return self::whereBetween('date', [$startDate, $endDate])
            ->selectRaw('
                frost_risk,
                COUNT(*) as days_count
            ')
            ->groupBy('frost_risk')
            ->orderByRaw('
                CASE frost_risk
                    WHEN "severe_frost" THEN 1
                    WHEN "frost" THEN 2
                    WHEN "light_frost_risk" THEN 3
                    WHEN "frost_risk" THEN 4
                    ELSE 5
                END
            ')
            ->get();
    }

    /**
     * Get planting suitability analysis
     */
    public static function getPlantingSuitabilityAnalysis($startDate, $endDate)
    {
        return self::whereBetween('date', [$startDate, $endDate])
            ->selectRaw('
                planting_suitability,
                COUNT(*) as days_count
            ')
            ->groupBy('planting_suitability')
            ->orderByRaw('
                CASE planting_suitability
                    WHEN "excellent" THEN 1
                    WHEN "good" THEN 2
                    WHEN "poor" THEN 3
                    ELSE 4
                END
            ')
            ->get();
    }
}
