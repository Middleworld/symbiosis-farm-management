<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WeatherDataIngestionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

/**
 * API Controller for Weather Data RAG Integration
 * Provides endpoints for AI systems to access 45+ years of weather data
 */
class WeatherApiController extends Controller
{
    protected $weatherDataService;

    public function __construct(WeatherDataIngestionService $weatherDataService)
    {
        $this->weatherDataService = $weatherDataService;
    }

    /**
     * Get weather insights for AI queries
     * POST /api/weather/insights
     */
    public function getInsights(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|max:1000',
            'context' => 'nullable|array',
            'context.crop' => 'nullable|string',
            'context.location' => 'nullable|string',
            'context.date_range' => 'nullable|array',
            'context.date_range.start' => 'nullable|date',
            'context.date_range.end' => 'nullable|date'
        ]);

        try {
            $insights = $this->weatherDataService->getWeatherInsights(
                $request->input('query'),
                $request->input('context', [])
            );

            return response()->json([
                'success' => true,
                'insights' => $insights,
                'query' => $request->input('query'),
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to generate weather insights',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get historical weather data for a date range
     * GET /api/weather/historical?start_date=2020-01-01&end_date=2020-12-31
     */
    public function getHistorical(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date|before:end_date',
            'end_date' => 'required|date|after:start_date',
            'limit' => 'nullable|integer|min:1|max:1000'
        ]);

        try {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $limit = $request->input('limit', 100);

            $data = \App\Models\WeatherHistoricalData::whereBetween('date', [$startDate, $endDate])
                ->orderBy('date')
                ->limit($limit)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $data,
                'count' => $data->count(),
                'date_range' => [
                    'start' => $startDate,
                    'end' => $endDate
                ],
                'limit' => $limit
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve historical weather data',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get monthly weather patterns
     * GET /api/weather/patterns/monthly?month=3&start_year=2010&end_year=2020
     */
    public function getMonthlyPatterns(Request $request): JsonResponse
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'start_year' => 'nullable|integer|min:1980|max:' . date('Y'),
            'end_year' => 'nullable|integer|min:1980|max:' . date('Y')
        ]);

        try {
            $month = $request->input('month');
            $startYear = $request->input('start_year');
            $endYear = $request->input('end_year');

            $patterns = \App\Models\WeatherHistoricalData::getMonthlyPatterns($month, $startYear, $endYear);

            return response()->json([
                'success' => true,
                'patterns' => $patterns,
                'month' => $month,
                'year_range' => [
                    'start' => $startYear,
                    'end' => $endYear
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve monthly patterns',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get growing degree days analysis
     * GET /api/weather/gdd?start_date=2020-01-01&end_date=2020-12-31&base_temp=10
     */
    public function getGrowingDegreeDays(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date|before:end_date',
            'end_date' => 'required|date|after:start_date',
            'base_temp' => 'nullable|numeric|min:0|max:30'
        ]);

        try {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $baseTemp = $request->input('base_temp', 10);

            $gddData = \App\Models\WeatherHistoricalData::getGddAccumulation($startDate, $endDate, $baseTemp);

            return response()->json([
                'success' => true,
                'gdd_analysis' => $gddData,
                'parameters' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'base_temperature' => $baseTemp
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to calculate growing degree days',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get frost risk analysis
     * GET /api/weather/frost?start_date=2020-01-01&end_date=2020-12-31
     */
    public function getFrostAnalysis(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date|before:end_date',
            'end_date' => 'required|date|after:start_date'
        ]);

        try {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            $frostAnalysis = \App\Models\WeatherHistoricalData::getFrostAnalysis($startDate, $endDate);

            return response()->json([
                'success' => true,
                'frost_analysis' => $frostAnalysis,
                'date_range' => [
                    'start' => $startDate,
                    'end' => $endDate
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to analyze frost risk',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get planting suitability analysis
     * GET /api/weather/planting?start_date=2020-01-01&end_date=2020-12-31
     */
    public function getPlantingSuitability(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date|before:end_date',
            'end_date' => 'required|date|after:start_date'
        ]);

        try {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            $suitabilityAnalysis = \App\Models\WeatherHistoricalData::getPlantingSuitabilityAnalysis($startDate, $endDate);

            return response()->json([
                'success' => true,
                'planting_suitability' => $suitabilityAnalysis,
                'date_range' => [
                    'start' => $startDate,
                    'end' => $endDate
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to analyze planting suitability',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get weather data statistics
     * GET /api/weather/stats
     */
    public function getStats(): JsonResponse
    {
        try {
            $stats = [
                'total_records' => \App\Models\WeatherHistoricalData::count(),
                'date_range' => [
                    'earliest' => \App\Models\WeatherHistoricalData::min('date'),
                    'latest' => \App\Models\WeatherHistoricalData::max('date')
                ],
                'years_covered' => \App\Models\WeatherHistoricalData::distinct('year')->count(),
                'frost_days' => \App\Models\WeatherHistoricalData::where('frost_risk', '!=', 'no_frost_risk')->count(),
                'excellent_planting_days' => \App\Models\WeatherHistoricalData::where('planting_suitability', 'excellent')->count(),
                'location' => [
                    'latitude' => \App\Models\WeatherHistoricalData::distinct('latitude')->first()?->latitude,
                    'longitude' => \App\Models\WeatherHistoricalData::distinct('longitude')->first()?->longitude
                ]
            ];

            return response()->json([
                'success' => true,
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve weather statistics',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
