<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class WeatherService
{
    private $openWeatherApiKey;
    private $baseUrl = 'https://api.openweathermap.org/data/2.5';
    private $historyUrl = 'https://api.openweathermap.org/data/3.0/onecall/timemachine';
    
    public function __construct()
    {
        $this->openWeatherApiKey = config('services.weather.openweather_key');
    }

    /**
     * Get current weather conditions (prioritize Met Office for accuracy)
     */
    public function getCurrentWeather()
    {
        $cacheKey = 'weather_current_' . $this->farmLatitude . '_' . $this->farmLongitude;
        
        return Cache::remember($cacheKey, 300, function () { // 5 minute cache
            // Try Met Office first for UK accuracy
            if ($this->metOfficeApiKey) {
                $metOfficeData = $this->getMetOfficeCurrentWeather();
                if ($metOfficeData) {
                    return $metOfficeData;
                }
            }
            
            // Fallback to OpenWeatherMap
            return $this->getOpenWeatherCurrentWeather();
        });
    }

    /**
     * Get 5-day forecast (Met Office)
     */
    public function getForecast($days = 5)
    {
        $cacheKey = "weather_forecast_{$days}_{$this->farmLatitude}_{$this->farmLongitude}";
        
        return Cache::remember($cacheKey, 1800, function () use ($days) { // 30 minute cache
            // Try Met Office first for UK accuracy
            if ($this->metOfficeApiKey) {
                $metOfficeData = $this->getMetOfficeForecast($days);
                if ($metOfficeData) {
                    return $metOfficeData;
                }
            }
            
            // Fallback to OpenWeatherMap
            return $this->getOpenWeatherForecast($days);
        });
    }

    /**
     * Get historical weather data (OpenWeatherMap specialty)
     */
    public function getHistoricalWeather($startDate, $endDate)
    {
        if (!$this->openWeatherApiKey) {
            throw new \Exception('OpenWeatherMap API key required for historical data');
        }

        $cacheKey = "weather_historical_{$startDate}_{$endDate}_{$this->farmLatitude}_{$this->farmLongitude}";
        
        return Cache::remember($cacheKey, 86400, function () use ($startDate, $endDate) { // 24 hour cache
            return $this->getOpenWeatherHistorical($startDate, $endDate);
        });
    }

    /**
     * Calculate Growing Degree Days (OpenWeatherMap agricultural data)
     */
    public function getGrowingDegreeDays($startDate, $endDate, $baseTemp = 10)
    {
        $historicalData = $this->getHistoricalWeather($startDate, $endDate);
        
        $gdd = 0;
        foreach ($historicalData['daily'] ?? [] as $day) {
            $maxTemp = $day['temp']['max'] ?? 0;
            $minTemp = $day['temp']['min'] ?? 0;
            $avgTemp = ($maxTemp + $minTemp) / 2;
            
            if ($avgTemp > $baseTemp) {
                $gdd += ($avgTemp - $baseTemp);
            }
        }
        
        return [
            'growing_degree_days' => round($gdd, 1),
            'base_temperature' => $baseTemp,
            'period' => [
                'start' => $startDate,
                'end' => $endDate
            ]
        ];
    }

    /**
     * Get frost risk analysis
     */
    public function getFrostRisk($days = 7)
    {
        $forecast = $this->getForecast($days);
        $frostRisk = [];
        
        foreach ($forecast['daily'] ?? [] as $day) {
            $minTemp = $day['temp']['min'] ?? $day['min_temp'] ?? 999;
            $date = $day['date'] ?? $day['dt'];
            
            $risk = 'none';
            if ($minTemp <= 0) {
                $risk = 'high';
            } elseif ($minTemp <= 2) {
                $risk = 'medium';
            } elseif ($minTemp <= 5) {
                $risk = 'low';
            }
            
            $frostRisk[] = [
                'date' => $date,
                'min_temp' => $minTemp,
                'risk' => $risk,
                'frost_warning' => $minTemp <= 0
            ];
        }
        
        return $frostRisk;
    }

    /**
     * Analyze optimal planting windows based on historical data
     */
    public function analyzeOptimalPlantingWindow($cropName, $years = 5)
    {
        // Get historical data for the last few years during planting season
        $results = [];
        $currentYear = date('Y');
        
        for ($year = $currentYear - $years; $year < $currentYear; $year++) {
            // Analyze March-May planting window
            $springStart = "{$year}-03-01";
            $springEnd = "{$year}-05-31";
            
            try {
                $historicalData = $this->getHistoricalWeather($springStart, $springEnd);
                $gdd = $this->getGrowingDegreeDays($springStart, $springEnd);
                
                // Analyze conditions
                $frostDays = 0;
                $goodPlantingDays = 0;
                
                foreach ($historicalData['daily'] ?? [] as $day) {
                    $minTemp = $day['temp']['min'] ?? 0;
                    $maxTemp = $day['temp']['max'] ?? 0;
                    $rainfall = $day['rain']['1h'] ?? 0;
                    
                    if ($minTemp <= 0) {
                        $frostDays++;
                    }
                    
                    // Good planting day: no frost, temps 8-25°C, no heavy rain
                    if ($minTemp > 2 && $maxTemp < 25 && $rainfall < 5) {
                        $goodPlantingDays++;
                    }
                }
                
                $results[$year] = [
                    'year' => $year,
                    'frost_days' => $frostDays,
                    'good_planting_days' => $goodPlantingDays,
                    'growing_degree_days' => $gdd['growing_degree_days'],
                    'last_frost_date' => $this->findLastFrostDate($historicalData),
                    'first_warm_spell' => $this->findFirstWarmSpell($historicalData)
                ];
                
            } catch (\Exception $e) {
                Log::warning("Failed to get historical data for {$year}: " . $e->getMessage());
            }
        }
        
        return [
            'crop' => $cropName,
            'analysis_period' => $years . ' years',
            'yearly_data' => $results,
            'recommendations' => $this->generatePlantingRecommendations($results)
        ];
    }

    /**
     * Met Office current weather implementation
     */
    protected function getMetOfficeCurrentWeather()
    {
        try {
            // Met Office API implementation
            $response = Http::timeout(10)->get('https://api-metoffice.apiconnect.ibmcloud.com/metoffice/production/v0/forecasts/point/hourly', [
                'latitude' => $this->farmLatitude,
                'longitude' => $this->farmLongitude,
                'includeLocationName' => true
            ], [
                'X-IBM-Client-Id' => $this->metOfficeApiKey,
                'X-IBM-Client-Secret' => env('MET_OFFICE_CLIENT_SECRET'),
                'accept' => 'application/json'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                return [
                    'source' => 'met_office',
                    'temperature' => $data['features'][0]['properties']['timeSeries'][0]['screenTemperature'] ?? null,
                    'humidity' => $data['features'][0]['properties']['timeSeries'][0]['screenRelativeHumidity'] ?? null,
                    'wind_speed' => $data['features'][0]['properties']['timeSeries'][0]['windSpeed10m'] ?? null,
                    'wind_direction' => $data['features'][0]['properties']['timeSeries'][0]['windDirectionFrom10m'] ?? null,
                    'pressure' => $data['features'][0]['properties']['timeSeries'][0]['mslp'] ?? null,
                    'visibility' => $data['features'][0]['properties']['timeSeries'][0]['visibility'] ?? null,
                    'weather_description' => $data['features'][0]['properties']['timeSeries'][0]['significantWeatherCode'] ?? null,
                    'timestamp' => now()
                ];
            }
            
        } catch (\Exception $e) {
            Log::warning('Met Office API failed: ' . $e->getMessage());
        }
        
        return null;
    }

    /**
     * OpenWeatherMap current weather implementation
     */
    protected function getOpenWeatherCurrentWeather()
    {
        try {
            $response = Http::timeout(10)->get('https://api.openweathermap.org/data/2.5/weather', [
                'lat' => $this->farmLatitude,
                'lon' => $this->farmLongitude,
                'appid' => $this->openWeatherApiKey,
                'units' => 'metric'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                return [
                    'source' => 'openweathermap',
                    'temperature' => $data['main']['temp'] ?? null,
                    'feels_like' => $data['main']['feels_like'] ?? null,
                    'humidity' => $data['main']['humidity'] ?? null,
                    'pressure' => $data['main']['pressure'] ?? null,
                    'wind_speed' => $data['wind']['speed'] ?? null,
                    'wind_direction' => $data['wind']['deg'] ?? null,
                    'visibility' => $data['visibility'] ?? null,
                    'weather_description' => $data['weather'][0]['description'] ?? null,
                    'weather_icon' => $data['weather'][0]['icon'] ?? null,
                    'timestamp' => now()
                ];
            }
            
        } catch (\Exception $e) {
            Log::error('OpenWeatherMap current weather failed: ' . $e->getMessage());
        }
        
        return null;
    }

    /**
     * OpenWeatherMap forecast implementation
     */
    protected function getOpenWeatherForecast($days = 5)
    {
        try {
            $response = Http::timeout(10)->get('https://api.openweathermap.org/data/2.5/forecast', [
                'lat' => $this->farmLatitude,
                'lon' => $this->farmLongitude,
                'appid' => $this->openWeatherApiKey,
                'units' => 'metric',
                'cnt' => $days * 8 // 8 forecasts per day (3-hour intervals)
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // Group by days
                $dailyForecasts = [];
                foreach ($data['list'] ?? [] as $forecast) {
                    $date = date('Y-m-d', $forecast['dt']);
                    
                    if (!isset($dailyForecasts[$date])) {
                        $dailyForecasts[$date] = [
                            'date' => $date,
                            'temp' => ['min' => 999, 'max' => -999],
                            'humidity' => [],
                            'weather' => [],
                            'rain' => 0
                        ];
                    }
                    
                    $temp = $forecast['main']['temp'];
                    $dailyForecasts[$date]['temp']['min'] = min($dailyForecasts[$date]['temp']['min'], $temp);
                    $dailyForecasts[$date]['temp']['max'] = max($dailyForecasts[$date]['temp']['max'], $temp);
                    $dailyForecasts[$date]['humidity'][] = $forecast['main']['humidity'];
                    $dailyForecasts[$date]['weather'][] = $forecast['weather'][0]['description'];
                    $dailyForecasts[$date]['rain'] += $forecast['rain']['3h'] ?? 0;
                }
                
                return [
                    'source' => 'openweathermap',
                    'daily' => array_values($dailyForecasts),
                    'timestamp' => now()
                ];
            }
            
        } catch (\Exception $e) {
            Log::error('OpenWeatherMap forecast failed: ' . $e->getMessage());
        }
        
        return null;
    }

    /**
     * Helper methods for analysis
     */
    protected function findLastFrostDate($historicalData)
    {
        $lastFrost = null;
        foreach ($historicalData['daily'] ?? [] as $day) {
            if (($day['temp']['min'] ?? 0) <= 0) {
                $lastFrost = $day['dt'] ?? $day['date'];
            }
        }
        return $lastFrost;
    }

    protected function findFirstWarmSpell($historicalData)
    {
        $consecutiveWarmDays = 0;
        foreach ($historicalData['daily'] ?? [] as $day) {
            if (($day['temp']['max'] ?? 0) >= 15) {
                $consecutiveWarmDays++;
                if ($consecutiveWarmDays >= 3) {
                    return $day['dt'] ?? $day['date'];
                }
            } else {
                $consecutiveWarmDays = 0;
            }
        }
        return null;
    }

    protected function generatePlantingRecommendations($yearlyData)
    {
        // Analyze the data to generate recommendations
        if (empty($yearlyData)) {
            return ['error' => 'Insufficient data for recommendations'];
        }

        $avgLastFrost = null;
        $avgGoodPlantingDays = array_sum(array_column($yearlyData, 'good_planting_days')) / count($yearlyData);
        
        return [
            'recommended_earliest_planting' => 'Early April (after average last frost)',
            'average_good_planting_days' => round($avgGoodPlantingDays),
            'frost_risk_period' => 'March - early April',
            'optimal_window' => 'Mid April - early May'
        ];
    }

    /**
     * Get field work conditions and recommendations
     */
    public function getFieldWorkConditions($days = 5)
    {
        $forecast = $this->getForecast($days);
        $conditions = [];
        
        foreach ($forecast['daily'] ?? [] as $day) {
            $date = $day['date'];
            $minTemp = $day['temp']['min'] ?? 0;
            $maxTemp = $day['temp']['max'] ?? 0;
            $rainfall = $day['rain'] ?? 0;
            $windSpeed = $this->getWindSpeedFromForecast($day);
            
            $dayConditions = [];
            
            // Spraying conditions
            if ($windSpeed < 10 && $rainfall < 1 && $minTemp > 5) {
                $dayConditions[] = 'Good for spraying';
            } else {
                if ($windSpeed >= 10) $dayConditions[] = 'Avoid spraying (windy)';
                if ($rainfall >= 1) $dayConditions[] = 'Avoid spraying (rain)';
                if ($minTemp <= 5) $dayConditions[] = 'Avoid spraying (cold)';
            }
            
            // Planting conditions
            if ($minTemp > 8 && $maxTemp < 25 && $rainfall < 5) {
                $dayConditions[] = 'Good for planting';
            } else {
                $dayConditions[] = 'Poor planting conditions';
            }
            
            // Harvesting conditions
            if ($rainfall < 2 && $windSpeed < 15) {
                $dayConditions[] = 'Good for harvesting';
            } else {
                $dayConditions[] = 'Poor harvesting conditions';
            }
            
            // Field access
            if ($rainfall > 10) {
                $dayConditions[] = 'Poor field access (wet)';
            } else {
                $dayConditions[] = 'Good field access';
            }
            
            $conditions[] = [
                'date' => $date,
                'temperature_range' => "{$minTemp}°C - {$maxTemp}°C",
                'rainfall' => "{$rainfall}mm",
                'wind_speed' => "{$windSpeed} mph",
                'conditions' => $dayConditions,
                'overall_rating' => $this->calculateWorkingDayRating($minTemp, $maxTemp, $rainfall, $windSpeed)
            ];
        }
        
        return $conditions;
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

    /**
     * Extract wind speed from forecast data
     */
    protected function getWindSpeedFromForecast($day)
    {
        // Try different possible wind speed keys
        return $day['wind_speed'] ?? $day['wind']['speed'] ?? 0;
    }

    /**
     * Calculate growing degree days from historical data
     */
    public function calculateGrowingDegreeDays($historicalData, $baseTemp = 10)
    {
        $gdd = 0;
        
        foreach ($historicalData as $day) {
            $maxTemp = $day['temp_max'] ?? $day['temp']['max'] ?? 0;
            $minTemp = $day['temp_min'] ?? $day['temp']['min'] ?? 0;
            $avgTemp = ($maxTemp + $minTemp) / 2;
            
            if ($avgTemp > $baseTemp) {
                $gdd += ($avgTemp - $baseTemp);
            }
        }
        
        return round($gdd, 1);
    }

    /**
     * Get OpenWeatherMap historical data
     */
    protected function getOpenWeatherHistorical($startDate, $endDate)
    {
        try {
            $startTimestamp = strtotime($startDate);
            $endTimestamp = strtotime($endDate);
            
            $response = Http::timeout(15)->get('https://api.openweathermap.org/data/3.0/onecall/timemachine', [
                'lat' => $this->farmLatitude,
                'lon' => $this->farmLongitude,
                'dt' => $startTimestamp,
                'appid' => $this->openWeatherApiKey,
                'units' => 'metric'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // Format the data for our use
                $formattedData = [];
                foreach ($data['data'] ?? [] as $dataPoint) {
                    $formattedData[] = [
                        'date' => date('Y-m-d', $dataPoint['dt']),
                        'temp_max' => $dataPoint['temp'] ?? 0,
                        'temp_min' => $dataPoint['temp'] ?? 0, // Historical API gives hourly data
                        'temp_avg' => $dataPoint['temp'] ?? 0,
                        'precipitation' => $dataPoint['rain']['1h'] ?? 0,
                        'humidity' => $dataPoint['humidity'] ?? 0,
                        'wind_speed' => $dataPoint['wind_speed'] ?? 0
                    ];
                }
                
                return $formattedData;
            }
            
        } catch (\Exception $e) {
            Log::error('OpenWeatherMap historical data failed: ' . $e->getMessage());
        }
        
        return [];
    }

    /**
     * Get Met Office forecast
     */
    protected function getMetOfficeForecast($days = 5)
    {
        try {
            $response = Http::timeout(10)->get('https://api-metoffice.apiconnect.ibmcloud.com/metoffice/production/v0/forecasts/point/daily', [
                'latitude' => $this->farmLatitude,
                'longitude' => $this->farmLongitude,
                'includeLocationName' => true
            ], [
                'X-IBM-Client-Id' => $this->metOfficeApiKey,
                'X-IBM-Client-Secret' => env('MET_OFFICE_CLIENT_SECRET'),
                'accept' => 'application/json'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                $dailyForecasts = [];
                $timeSeries = $data['features'][0]['properties']['timeSeries'] ?? [];
                
                foreach (array_slice($timeSeries, 0, $days) as $forecast) {
                    $dailyForecasts[] = [
                        'date' => substr($forecast['time'], 0, 10), // Extract date from ISO string
                        'temp' => [
                            'min' => $forecast['nightMinScreenTemperature'] ?? 0,
                            'max' => $forecast['dayMaxScreenTemperature'] ?? 0
                        ],
                        'rain' => $forecast['totalPrecipAmount'] ?? 0,
                        'wind_speed' => $forecast['midday10MWindSpeed'] ?? 0,
                        'humidity' => $forecast['middayRelativeHumidity'] ?? 0
                    ];
                }
                
                return [
                    'source' => 'met_office',
                    'daily' => $dailyForecasts,
                    'timestamp' => now()
                ];
            }
            
        } catch (\Exception $e) {
            Log::warning('Met Office forecast failed: ' . $e->getMessage());
        }
        
        return null;
    }
}
