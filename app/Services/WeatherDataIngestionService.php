<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use App\Models\WeatherHistoricalData;
use App\Services\AiIngestionService;

/**
 * Service for ingesting and managing 45+ years of historical weather data for RAG
 */
class WeatherDataIngestionService
{
    protected $weatherService;
    protected $aiIngestionService;

    public function __construct(
        WeatherService $weatherService,
        AiIngestionService $aiIngestionService
    ) {
        $this->weatherService = $weatherService;
        $this->aiIngestionService = $aiIngestionService;
    }

    /**
     * Ingest historical weather data for a date range
     */
    public function ingestHistoricalData(string $startDate, string $endDate, int $batchSize = 30): array
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $totalDays = $start->diffInDays($end);
        $batches = ceil($totalDays / $batchSize);

        $results = [
            'total_days' => $totalDays,
            'batches' => $batches,
            'processed' => 0,
            'errors' => 0,
            'data_points' => 0
        ];

        Log::info("Starting weather data ingestion: {$totalDays} days in {$batches} batches");

        for ($i = 0; $i < $batches; $i++) {
            $batchStart = $start->copy()->addDays($i * $batchSize);
            $batchEnd = $batchStart->copy()->addDays($batchSize - 1);

            if ($batchEnd->greaterThan($end)) {
                $batchEnd = $end->copy();
            }

            try {
                $batchResult = $this->processBatch($batchStart, $batchEnd);
                $results['processed'] += $batchResult['processed'];
                $results['data_points'] += $batchResult['data_points'];

                Log::info("Batch " . ($i + 1) . "/{$batches} completed: {$batchResult['processed']} days, {$batchResult['data_points']} data points");

            } catch (\Exception $e) {
                $results['errors']++;
                Log::error("Batch " . ($i + 1) . " failed: " . $e->getMessage());
            }

            // Rate limiting - avoid overwhelming the API
            sleep(1);
        }

        // Create RAG dataset after ingestion
        $this->createWeatherRagDataset($startDate, $endDate);

        return $results;
    }

    /**
     * Process a batch of historical weather data
     */
    protected function processBatch(Carbon $startDate, Carbon $endDate): array
    {
        $processed = 0;
        $dataPoints = 0;

        try {
            $historicalData = $this->weatherService->getHistoricalWeather(
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d')
            );

            if (!empty($historicalData['daily'])) {
                foreach ($historicalData['daily'] as $dayData) {
                    $this->storeWeatherDataPoint($dayData, $startDate->year);
                    $dataPoints++;
                }
            }

            $processed = $startDate->diffInDays($endDate) + 1;

        } catch (\Exception $e) {
            Log::error("Failed to process batch {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}: " . $e->getMessage());
        }

        return [
            'processed' => $processed,
            'data_points' => $dataPoints
        ];
    }

    /**
     * Store individual weather data point
     */
    protected function storeWeatherDataPoint(array $dayData, int $year): void
    {
        $date = isset($dayData['dt']) ? Carbon::createFromTimestamp($dayData['dt']) : null;

        if (!$date) {
            return;
        }

        WeatherHistoricalData::updateOrCreate(
            [
                'date' => $date->format('Y-m-d'),
                'latitude' => $this->weatherService->getFarmLatitude(),
                'longitude' => $this->weatherService->getFarmLongitude()
            ],
            [
                'year' => $year,
                'month' => $date->month,
                'day' => $date->day,
                'temperature_max' => $dayData['temp']['max'] ?? null,
                'temperature_min' => $dayData['temp']['min'] ?? null,
                'temperature_avg' => isset($dayData['temp']['max'], $dayData['temp']['min'])
                    ? ($dayData['temp']['max'] + $dayData['temp']['min']) / 2
                    : null,
                'humidity' => $dayData['humidity'] ?? null,
                'pressure' => $dayData['pressure'] ?? null,
                'wind_speed' => $dayData['wind_speed'] ?? null,
                'wind_direction' => $dayData['wind_deg'] ?? null,
                'precipitation' => $dayData['rain'] ?? 0,
                'snowfall' => $dayData['snow'] ?? 0,
                'cloudiness' => $dayData['clouds'] ?? null,
                'weather_condition' => $dayData['weather'][0]['main'] ?? null,
                'weather_description' => $dayData['weather'][0]['description'] ?? null,
                'uv_index' => $dayData['uvi'] ?? null,
                'sunrise' => isset($dayData['sunrise']) ? Carbon::createFromTimestamp($dayData['sunrise']) : null,
                'sunset' => isset($dayData['sunset']) ? Carbon::createFromTimestamp($dayData['sunset']) : null,
                'growing_degree_days' => $this->calculateGdd($dayData),
                'frost_risk' => $this->assessFrostRisk($dayData),
                'planting_suitability' => $this->assessPlantingSuitability($dayData),
                'raw_data' => json_encode($dayData)
            ]
        );
    }

    /**
     * Calculate Growing Degree Days for the day
     */
    protected function calculateGdd(array $dayData): ?float
    {
        $maxTemp = $dayData['temp']['max'] ?? null;
        $minTemp = $dayData['temp']['min'] ?? null;
        $baseTemp = 10; // Base temperature for most crops

        if ($maxTemp === null || $minTemp === null) {
            return null;
        }

        $avgTemp = ($maxTemp + $minTemp) / 2;

        if ($avgTemp > $baseTemp) {
            return $avgTemp - $baseTemp;
        }

        return 0;
    }

    /**
     * Assess frost risk for the day
     */
    protected function assessFrostRisk(array $dayData): string
    {
        $minTemp = $dayData['temp']['min'] ?? 999;

        if ($minTemp <= -2) return 'severe_frost';
        if ($minTemp <= 0) return 'frost';
        if ($minTemp <= 2) return 'light_frost_risk';
        if ($minTemp <= 5) return 'frost_risk';
        return 'no_frost_risk';
    }

    /**
     * Assess planting suitability for the day
     */
    protected function assessPlantingSuitability(array $dayData): string
    {
        $minTemp = $dayData['temp']['min'] ?? 0;
        $maxTemp = $dayData['temp']['max'] ?? 0;
        $precipitation = $dayData['rain'] ?? 0;
        $windSpeed = $dayData['wind_speed'] ?? 0;

        if ($minTemp < 5 || $maxTemp > 25 || $precipitation > 5 || $windSpeed > 15) {
            return 'poor';
        }

        if ($minTemp >= 8 && $maxTemp <= 20 && $precipitation <= 2 && $windSpeed <= 10) {
            return 'excellent';
        }

        return 'good';
    }

    /**
     * Create RAG dataset from ingested weather data
     */
    protected function createWeatherRagDataset(string $startDate, string $endDate): void
    {
        try {
            // Create AI ingestion task for weather data
            $task = $this->aiIngestionService->createTask('weather_historical_rag', [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'data_points' => WeatherHistoricalData::whereBetween('date', [$startDate, $endDate])->count()
            ]);

            Log::info("Created RAG dataset task for weather data: {$startDate} to {$endDate}");

        } catch (\Exception $e) {
            Log::error("Failed to create RAG dataset task: " . $e->getMessage());
        }
    }

    /**
     * Get weather insights for AI/RAG queries
     */
    public function getWeatherInsights(string $query, array $context = []): array
    {
        $insights = [];

        // Extract date ranges from query
        $dateRange = $this->extractDateRange($query);

        if ($dateRange) {
            $insights = array_merge($insights, $this->getHistoricalPatterns($dateRange));
        }

        // Extract crop or agricultural context
        if (isset($context['crop'])) {
            $insights = array_merge($insights, $this->getCropSpecificInsights($context['crop'], $dateRange));
        }

        // Extract location context
        if (isset($context['location'])) {
            $insights = array_merge($insights, $this->getLocationSpecificInsights($context['location']));
        }

        return [
            'insights' => $insights,
            'data_sources' => ['historical_weather_db', 'weather_patterns'],
            'confidence' => $this->calculateConfidence($insights),
            'query_context' => $context
        ];
    }

    /**
     * Extract date range from natural language query
     */
    protected function extractDateRange(string $query): ?array
    {
        // Simple date range extraction - could be enhanced with NLP
        $patterns = [
            '/(\d{4}) to (\d{4})/' => function($matches) {
                return ['start' => $matches[1] . '-01-01', 'end' => $matches[2] . '-12-31'];
            },
            '/last (\d+) years/' => function($matches) {
                $years = (int)$matches[1];
                return [
                    'start' => now()->subYears($years)->format('Y-01-01'),
                    'end' => now()->format('Y-12-31')
                ];
            },
            '/(\d{4})/' => function($matches) {
                return ['start' => $matches[1] . '-01-01', 'end' => $matches[1] . '-12-31'];
            }
        ];

        foreach ($patterns as $pattern => $handler) {
            if (preg_match($pattern, $query, $matches)) {
                return $handler($matches);
            }
        }

        return null;
    }

    /**
     * Get historical weather patterns
     */
    protected function getHistoricalPatterns(array $dateRange): array
    {
        $patterns = [];

        // Average temperatures by month
        $monthlyAvg = WeatherHistoricalData::selectRaw('
                MONTH(date) as month,
                AVG(temperature_avg) as avg_temp,
                AVG(precipitation) as avg_precipitation,
                MIN(temperature_min) as min_temp,
                MAX(temperature_max) as max_temp
            ')
            ->whereBetween('date', [$dateRange['start'], $dateRange['end']])
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        if ($monthlyAvg->count() > 0) {
            $patterns[] = [
                'type' => 'monthly_patterns',
                'data' => $monthlyAvg->toArray(),
                'description' => 'Average monthly weather patterns for the specified period'
            ];
        }

        // Frost frequency
        $frostFrequency = WeatherHistoricalData::selectRaw('
                YEAR(date) as year,
                COUNT(*) as frost_days
            ')
            ->where('frost_risk', '!=', 'no_frost_risk')
            ->whereBetween('date', [$dateRange['start'], $dateRange['end']])
            ->groupBy('year')
            ->orderBy('year')
            ->get();

        if ($frostFrequency->count() > 0) {
            $patterns[] = [
                'type' => 'frost_patterns',
                'data' => $frostFrequency->toArray(),
                'description' => 'Frost frequency patterns by year'
            ];
        }

        return $patterns;
    }

    /**
     * Get crop-specific weather insights
     */
    protected function getCropSpecificInsights(string $crop, ?array $dateRange = null): array
    {
        $insights = [];

        // Define crop-specific weather requirements
        $cropRequirements = $this->getCropWeatherRequirements($crop);

        if ($dateRange) {
            $optimalDays = WeatherHistoricalData::whereBetween('date', [$dateRange['start'], $dateRange['end']])
                ->where('planting_suitability', 'excellent')
                ->count();

            $insights[] = [
                'type' => 'planting_suitability',
                'crop' => $crop,
                'optimal_days' => $optimalDays,
                'description' => "Number of days with excellent planting conditions for {$crop}"
            ];
        }

        return $insights;
    }

    /**
     * Get crop-specific weather requirements
     */
    protected function getCropWeatherRequirements(string $crop): array
    {
        $requirements = [
            'lettuce' => ['min_temp' => 5, 'max_temp' => 20, 'max_precipitation' => 2],
            'carrots' => ['min_temp' => 8, 'max_temp' => 25, 'max_precipitation' => 3],
            'potatoes' => ['min_temp' => 10, 'max_temp' => 20, 'max_precipitation' => 2],
            'tomatoes' => ['min_temp' => 12, 'max_temp' => 25, 'max_precipitation' => 2],
            'beans' => ['min_temp' => 10, 'max_temp' => 25, 'max_precipitation' => 3],
            'peas' => ['min_temp' => 8, 'max_temp' => 20, 'max_precipitation' => 2],
            'cabbage' => ['min_temp' => 5, 'max_temp' => 20, 'max_precipitation' => 3],
            'spinach' => ['min_temp' => 5, 'max_temp' => 18, 'max_precipitation' => 2],
            'kale' => ['min_temp' => 5, 'max_temp' => 20, 'max_precipitation' => 3],
            'broccoli' => ['min_temp' => 7, 'max_temp' => 20, 'max_precipitation' => 2]
        ];

        return $requirements[strtolower($crop)] ?? $requirements['lettuce'];
    }

    /**
     * Calculate confidence score for insights
     */
    protected function calculateConfidence(array $insights): float
    {
        if (empty($insights)) {
            return 0.0;
        }

        $totalDataPoints = 0;
        foreach ($insights as $insight) {
            if (isset($insight['data']) && is_array($insight['data'])) {
                $totalDataPoints += count($insight['data']);
            }
        }

        // Confidence based on data volume and diversity
        $baseConfidence = min($totalDataPoints / 100, 1.0);
        $diversityBonus = min(count($insights) / 5, 0.2);

        return round($baseConfidence + $diversityBonus, 2);
    }
}
