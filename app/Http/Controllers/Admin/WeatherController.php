<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\WeatherService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WeatherController extends Controller
{
    protected $weatherService;

    public function __construct(WeatherService $weatherService)
    {
        $this->weatherService = $weatherService;
    }

    /**
     * Weather dashboard view
     */
    public function index()
    {
        try {
            $currentWeather = $this->weatherService->getCurrentWeather();
            $forecast = $this->weatherService->getForecast(7);
            $frostRisk = $this->weatherService->getFrostRisk(7);
            $fieldConditions = $this->weatherService->getFieldWorkConditions(5);
            
            // Calculate today's GDD from current temperature
            $todayGDD = 0;
            if ($currentWeather && isset($currentWeather['temperature'])) {
                $temp = $currentWeather['temperature'];
                $baseTemp = 10; // Base temperature for GDD calculation
                if ($temp > $baseTemp) {
                    $todayGDD = $temp - $baseTemp;
                }
            }
            
            return view('admin.weather.dashboard', compact(
                'currentWeather',
                'forecast', 
                'frostRisk',
                'fieldConditions',
                'todayGDD'
            ));
            
        } catch (\Exception $e) {
            Log::error('Weather dashboard error: ' . $e->getMessage());
            
            return view('admin.weather.dashboard', [
                'error' => 'Unable to load weather data: ' . $e->getMessage(),
                'currentWeather' => null,
                'forecast' => null,
                'frostRisk' => null
            ]);
        }
    }

    /**
     * Get current weather as JSON
     */
    public function getCurrentWeather()
    {
        try {
            $weather = $this->weatherService->getCurrentWeather();
            
            return response()->json([
                'success' => true,
                'data' => $weather,
                'timestamp' => now()
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get weather forecast as JSON
     */
    public function getForecast(Request $request)
    {
        try {
            $days = $request->get('days', 5);
            $forecast = $this->weatherService->getForecast($days);
            
            return response()->json([
                'success' => true,
                'data' => $forecast,
                'timestamp' => now()
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get frost risk analysis
     */
    public function getFrostRisk(Request $request)
    {
        try {
            $days = $request->get('days', 7);
            $frostRisk = $this->weatherService->getFrostRisk($days);
            
            return response()->json([
                'success' => true,
                'data' => $frostRisk,
                'timestamp' => now()
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Analyze optimal planting windows for crops
     */
    public function analyzePlantingWindow(Request $request)
    {
        try {
            $cropName = $request->get('crop', 'General');
            $years = $request->get('years', 5);
            
            $analysis = $this->weatherService->analyzeOptimalPlantingWindow($cropName, $years);
            
            return response()->json([
                'success' => true,
                'data' => $analysis,
                'timestamp' => now()
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Growing Degree Days calculation
     */
    public function getGrowingDegreeDays(Request $request)
    {
        try {
            $startDate = $request->get('start_date', date('Y-m-01')); // First of current month
            $endDate = $request->get('end_date', date('Y-m-d')); // Today
            $baseTemp = $request->get('base_temp', 10); // Default base temperature
            
            $gdd = $this->weatherService->getGrowingDegreeDays($startDate, $endDate, $baseTemp);
            
            return response()->json([
                'success' => true,
                'data' => $gdd,
                'timestamp' => now()
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Historical weather data
     */
    public function getHistoricalWeather(Request $request)
    {
        try {
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');
            
            if (!$startDate || !$endDate) {
                return response()->json([
                    'success' => false,
                    'error' => 'start_date and end_date parameters are required'
                ], 400);
            }
            
            $historical = $this->weatherService->getHistoricalWeather($startDate, $endDate);
            
            return response()->json([
                'success' => true,
                'data' => $historical,
                'timestamp' => now()
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Weather alerts and notifications
     */
    public function getWeatherAlerts()
    {
        try {
            $alerts = [];
            
            // Get frost warnings
            $frostRisk = $this->weatherService->getFrostRisk(3);
            foreach ($frostRisk as $day) {
                if ($day['frost_warning']) {
                    $alerts[] = [
                        'type' => 'frost_warning',
                        'severity' => 'high',
                        'title' => 'Frost Warning',
                        'message' => "Frost expected on {$day['date']} with minimum temperature {$day['min_temp']}°C",
                        'date' => $day['date'],
                        'action_required' => 'Protect sensitive plants and crops'
                    ];
                }
            }
            
            // Get current weather for extreme conditions
            $currentWeather = $this->weatherService->getCurrentWeather();
            if ($currentWeather) {
                $temp = $currentWeather['temperature'] ?? 0;
                $windSpeed = $currentWeather['wind_speed'] ?? 0;
                
                // High wind warning (unsuitable for spraying)
                if ($windSpeed > 15) {
                    $alerts[] = [
                        'type' => 'high_wind',
                        'severity' => 'medium',
                        'title' => 'High Wind Warning',
                        'message' => "Wind speed {$windSpeed} mph - avoid spraying operations",
                        'date' => date('Y-m-d'),
                        'action_required' => 'Postpone spraying activities'
                    ];
                }
                
                // Extreme temperature warnings
                if ($temp < 0) {
                    $alerts[] = [
                        'type' => 'extreme_cold',
                        'severity' => 'high',
                        'title' => 'Extreme Cold',
                        'message' => "Current temperature {$temp}°C - check livestock and protect plants",
                        'date' => date('Y-m-d'),
                        'action_required' => 'Check water systems and provide shelter'
                    ];
                } elseif ($temp > 30) {
                    $alerts[] = [
                        'type' => 'extreme_heat',
                        'severity' => 'medium',
                        'title' => 'High Temperature',
                        'message' => "Current temperature {$temp}°C - increase irrigation and provide shade",
                        'date' => date('Y-m-d'),
                        'action_required' => 'Monitor water stress in crops'
                    ];
                }
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'alerts' => $alerts,
                    'count' => count($alerts)
                ],
                'timestamp' => now()
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Field work recommendations based on weather
     */
    public function getFieldWorkRecommendations()
    {
        try {
            $forecast = $this->weatherService->getForecast(7);
            $recommendations = [];
            
            foreach ($forecast['daily'] ?? [] as $day) {
                $date = $day['date'];
                $minTemp = $day['temp']['min'] ?? 0;
                $maxTemp = $day['temp']['max'] ?? 0;
                $rainfall = $day['rain'] ?? 0;
                $windSpeed = $day['wind_speed'] ?? 0;
                
                $conditions = [];
                
                // Spraying conditions
                if ($windSpeed < 10 && $rainfall < 1 && $minTemp > 5) {
                    $conditions[] = 'Good for spraying';
                } else {
                    $conditions[] = 'Avoid spraying (wind/rain/cold)';
                }
                
                // Planting conditions
                if ($minTemp > 8 && $maxTemp < 25 && $rainfall < 5) {
                    $conditions[] = 'Good for planting';
                }
                
                // Harvesting conditions
                if ($rainfall < 2 && $windSpeed < 15) {
                    $conditions[] = 'Good for harvesting';
                }
                
                // Field access
                if ($rainfall > 10) {
                    $conditions[] = 'Poor field access (wet)';
                } else {
                    $conditions[] = 'Good field access';
                }
                
                $recommendations[] = [
                    'date' => $date,
                    'temperature_range' => "{$minTemp}°C - {$maxTemp}°C",
                    'rainfall' => "{$rainfall}mm",
                    'wind_speed' => "{$windSpeed} mph",
                    'conditions' => $conditions,
                    'overall_rating' => $this->calculateWorkingDayRating($minTemp, $maxTemp, $rainfall, $windSpeed)
                ];
            }
            
            return response()->json([
                'success' => true,
                'data' => $recommendations,
                'timestamp' => now()
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate working day rating
     */
    protected function calculateWorkingDayRating($minTemp, $maxTemp, $rainfall, $windSpeed)
    {
        $score = 100;
        
        // Temperature penalties
        if ($minTemp < 5) $score -= 20;
        if ($maxTemp > 30) $score -= 15;
        
        // Rainfall penalties
        if ($rainfall > 5) $score -= 30;
        if ($rainfall > 15) $score -= 50;
        
        // Wind penalties
        if ($windSpeed > 15) $score -= 25;
        if ($windSpeed > 25) $score -= 40;
        
        $score = max(0, $score);
        
        if ($score >= 80) return 'Excellent';
        if ($score >= 60) return 'Good';
        if ($score >= 40) return 'Fair';
        return 'Poor';
    }
}
