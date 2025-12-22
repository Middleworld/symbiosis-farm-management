<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class WeatherService
{
    protected $openWeatherApiKey;
    protected $metOfficeApiKey;
    protected $metOfficeLandObservationsKey;
    protected $metOfficeSiteSpecificKey;
    protected $metOfficeAtmosphericKey;
    protected $metOfficeMapImagesKey;
    protected $weatherApiKey;
    protected $farmLatitude;
    protected $farmLongitude;

    public function __construct()
    {
        $weatherKeys = \App\Services\ApiKeyService::getWeatherApiKeys();
        
        $this->openWeatherApiKey = $weatherKeys['openweather'];
        $this->metOfficeApiKey = $weatherKeys['met_office'];
        
        // Trim whitespace from Met Office keys (they have embedded newlines in .env)
        $this->metOfficeLandObservationsKey = trim($weatherKeys['met_office_land_observations']);
        $this->metOfficeSiteSpecificKey = trim($weatherKeys['met_office_site_specific']);
        $this->metOfficeAtmosphericKey = trim($weatherKeys['met_office_atmospheric']);
        $this->metOfficeMapImagesKey = trim($weatherKeys['met_office_map_images']);
        
        $this->weatherApiKey = env('WEATHERAPI_KEY');
        
        // Your farm coordinates (update these with your actual location)
        $this->farmLatitude = $weatherKeys['latitude'];
        $this->farmLongitude = $weatherKeys['longitude'];
    }

    /**
     * Get current weather conditions (UK-optimized with WeatherAPI.com)
     */
    public function getCurrentWeather()
    {
        $cacheKey = 'weather_current_' . $this->farmLatitude . '_' . $this->farmLongitude;
        
        return Cache::remember($cacheKey, 300, function () { // 5 minute cache
            // Try WeatherAPI.com FIRST (best UK accuracy, 1M free calls/month)
            if ($this->weatherApiKey) {
                $weatherApiData = $this->getWeatherApiCurrentWeather();
                if ($weatherApiData) {
                    return $weatherApiData;
                }
            }
            
            // NOTE: Met Office DataHub free tier returns 403 Forbidden on all data endpoints
            // Free tier provides documentation access only, not actual weather data
            // Keeping code commented for reference if upgrading to paid tier in future
            // if ($this->metOfficeLandObservationsKey) {
            //     $landObsData = $this->getMetOfficeLandObservations();
            //     if ($landObsData) {
            //         return $landObsData;
            //     }
            // }
            
            // Fallback to OpenWeatherMap
            return $this->getOpenWeatherCurrentWeather();
        });
    }

    /**
     * Get 5-day forecast (WeatherAPI priority)
     */
    public function getForecast($days = 5)
    {
        $cacheKey = "weather_forecast_{$days}_{$this->farmLatitude}_{$this->farmLongitude}";
        
        return Cache::remember($cacheKey, 1800, function () use ($days) { // 30 minute cache
            // Try WeatherAPI.com first for UK accuracy
            if ($this->weatherApiKey) {
                $weatherApiData = $this->getWeatherApiForecast($days);
                if ($weatherApiData) {
                    return [
                        'source' => 'weatherapi',
                        'daily' => $weatherApiData,
                        'timestamp' => now()
                    ];
                }
            }
            
            // Try Met Office Site-Specific (best UK accuracy)
            if ($this->metOfficeSiteSpecificKey) {
                $siteSpecificData = $this->getMetOfficeSiteSpecificForecast($days);
                if ($siteSpecificData) {
                    return [
                        'source' => 'met_office_site_specific',
                        'daily' => $siteSpecificData,
                        'timestamp' => now()
                    ];
                }
            }
            
            // Try Met Office (if we get a proper key later)
            if ($this->metOfficeApiKey) {
                $metOfficeData = $this->getMetOfficeForecast($days);
                if ($metOfficeData) {
                    return [
                        'source' => 'met_office',
                        'daily' => $metOfficeData,
                        'timestamp' => now()
                    ];
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
     * Met Office Land Observations - ACTUAL UK weather station data!
     * API: /observation-land/1/nearest and /observation-land/1/{geohash}
     */
    protected function getMetOfficeLandObservations()
    {
        try {
            $baseUrl = 'https://data.hub.api.metoffice.gov.uk/observation-land/1';
            
            // Step 1: Find nearest observation station (cache this!)
            $geohashCacheKey = "met_office_geohash_{$this->farmLatitude}_{$this->farmLongitude}";
            $nearestStation = Cache::remember($geohashCacheKey, 86400, function () use ($baseUrl) {
                // Cache for 24 hours - stations don't move!
                $response = Http::timeout(10)->withHeaders([
                    'apikey' => $this->metOfficeLandObservationsKey,
                    'Accept' => 'application/json'
                ])->get("{$baseUrl}/nearest", [
                    'latitude' => $this->farmLatitude,
                    'longitude' => $this->farmLongitude
                ]);

                if ($response->successful()) {
                    return $response->json();
                }
                return null;
            });

            if (!$nearestStation || !isset($nearestStation['geohash'])) {
                Log::warning('Met Office: No nearby observation station found');
                return null;
            }

            $geohash = $nearestStation['geohash'];
            $stationName = $nearestStation['name'] ?? 'Unknown Station';
            $distance = $nearestStation['distance'] ?? 0;

            // Step 2: Get observations for this station
            $response = Http::timeout(10)->withHeaders([
                'apikey' => $this->metOfficeLandObservationsKey,
                'Accept' => 'application/json'
            ])->get("{$baseUrl}/{$geohash}");

            if (!$response->successful()) {
                Log::warning('Met Office: Failed to get observations for ' . $geohash);
                return null;
            }

            $observations = $response->json();
            
            if (empty($observations) || !is_array($observations)) {
                return null;
            }

            // Get the latest observation (first in the array)
            $latest = $observations[0];

            // Map Met Office weather codes to descriptions
            $weatherDescriptions = [
                0 => 'Clear night',
                1 => 'Sunny day',
                2 => 'Partly cloudy (night)',
                3 => 'Partly cloudy (day)',
                4 => 'Not used',
                5 => 'Mist',
                6 => 'Fog',
                7 => 'Cloudy',
                8 => 'Overcast',
                9 => 'Light rain shower (night)',
                10 => 'Light rain shower (day)',
                11 => 'Drizzle',
                12 => 'Light rain',
                13 => 'Heavy rain shower (night)',
                14 => 'Heavy rain shower (day)',
                15 => 'Heavy rain',
                16 => 'Sleet shower (night)',
                17 => 'Sleet shower (day)',
                18 => 'Sleet',
                19 => 'Hail shower (night)',
                20 => 'Hail shower (day)',
                21 => 'Hail',
                22 => 'Light snow shower (night)',
                23 => 'Light snow shower (day)',
                24 => 'Light snow',
                25 => 'Heavy snow shower (night)',
                26 => 'Heavy snow shower (day)',
                27 => 'Heavy snow',
                28 => 'Thunder shower (night)',
                29 => 'Thunder shower (day)',
                30 => 'Thunder'
            ];

            $weatherCode = $latest['weather_code'] ?? 0;
            $weatherDescription = $weatherDescriptions[$weatherCode] ?? 'Unknown';

            return [
                'source' => 'met_office_land_observations',
                'station' => $stationName,
                'distance_km' => round($distance, 2),
                'temperature' => $latest['temperature'] ?? null,
                'feels_like' => null, // Not provided in land observations
                'humidity' => $latest['humidity'] ?? null,
                'pressure' => $latest['mslp'] ?? null,  // Mean sea level pressure
                'wind_speed' => isset($latest['wind_speed']) ? $latest['wind_speed'] * 2.237 : null, // m/s to mph
                'wind_direction' => $latest['wind_direction'] ?? null,
                'wind_gust' => isset($latest['wind_gust']) ? $latest['wind_gust'] * 2.237 : null, // m/s to mph
                'visibility' => isset($latest['visibility']) ? $latest['visibility'] / 1000 : null, // meters to km
                'weather_code' => $weatherCode,
                'weather_description' => $weatherDescription,
                'pressure_tendency' => $latest['pressure_tendency'] ?? null, // R=rising, F=falling, S=steady
                'observation_time' => $latest['datetime'] ?? null,
                'timestamp' => now()
            ];

        } catch (\Exception $e) {
            Log::warning('Met Office Land Observations failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Met Office current weather implementation
     */
    protected function getMetOfficeCurrentWeather()
    {
        try {
            // Get location list first
            $locationResponse = Http::timeout(10)->withHeaders([
                'apikey' => $this->metOfficeApiKey,
                'accept' => 'application/json'
            ])->get('https://data.hub.api.metoffice.gov.uk/sitespecific/v0/site/list');

            if (!$locationResponse->successful()) {
                Log::warning('Failed to get Met Office locations: ' . $locationResponse->status());
                return null;
            }

            // Find nearest location
            $locations = $locationResponse->json()['Locations']['Location'] ?? [];
            $nearestLocation = $this->findNearestLocation($locations, $this->farmLatitude, $this->farmLongitude);
            
            if (!$nearestLocation) {
                return null;
            }

            // Get weather data for nearest location
            $response = Http::timeout(10)->withHeaders([
                'apikey' => $this->metOfficeApiKey,
                'accept' => 'application/json'
            ])->get("https://data.hub.api.metoffice.gov.uk/sitespecific/v0/site/{$nearestLocation['id']}", [
                'res' => '3hourly'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $periods = $data['SiteRep']['DV']['Location']['Period'] ?? [];
                
                if (!empty($periods)) {
                    $currentRep = $periods[0]['Rep'][0] ?? null;

                if ($currentRep) {
                    return [
                        'source' => 'met_office',
                        'temperature' => $currentRep['T'] ?? null,
                        'feels_like' => $currentRep['F'] ?? null,
                        'humidity' => $currentRep['H'] ?? null,
                        'wind_speed' => $currentRep['S'] ?? null,
                        'wind_direction' => $currentRep['D'] ?? null,
                        'wind_gust' => $currentRep['G'] ?? null,
                        'visibility' => $currentRep['V'] ?? null,
                        'pressure' => null, // Not available in 3-hourly
                        'weather_description' => $this->getMetOfficeWeatherType($currentRep['W'] ?? 0),
                        'timestamp' => now()
                    ];
                }
                }
            }

        } catch (\Exception $e) {
            Log::warning('Met Office API failed: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Met Office Site-Specific current weather implementation (FIXED)
     */
    protected function getMetOfficeSiteSpecificWeather()
    {
        try {
            // First, get the location ID for your coordinates
            $locationResponse = Http::timeout(10)->withHeaders([
                'apikey' => $this->metOfficeSiteSpecificKey,
                'accept' => 'application/json'
            ])->get('https://data.hub.api.metoffice.gov.uk/sitespecific/v0/site/list');

            if (!$locationResponse->successful()) {
                Log::warning('Failed to get Met Office locations: ' . $locationResponse->status());
                return null;
            }

            // Find nearest location (simplified - you'd want proper distance calculation)
            $locations = $locationResponse->json()['Locations']['Location'] ?? [];
            $nearestLocation = $this->findNearestLocation($locations, $this->farmLatitude, $this->farmLongitude);
            
            if (!$nearestLocation) {
                Log::warning('No nearby Met Office location found');
                return null;
            }

            // Now get the forecast for that location
            $response = Http::timeout(10)->withHeaders([
                'apikey' => $this->metOfficeSiteSpecificKey,
                'accept' => 'application/json'
            ])->get("https://data.hub.api.metoffice.gov.uk/sitespecific/v0/site/{$nearestLocation['id']}", [
                'res' => '3hourly'  // or 'daily'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $periods = $data['SiteRep']['DV']['Location']['Period'] ?? [];
                
                if (!empty($periods)) {
                    // Get the first period's first rep (closest to now)
                    $currentRep = $periods[0]['Rep'][0] ?? null;
                    
                    if ($currentRep) {
                        return [
                            'source' => 'met_office_site_specific',
                            'temperature' => $currentRep['T'] ?? null,  // Temperature in Celsius
                            'feels_like' => $currentRep['F'] ?? null,   // Feels like temperature
                            'humidity' => $currentRep['H'] ?? null,     // Screen relative humidity
                            'wind_speed' => $currentRep['S'] ?? null,   // Wind speed (mph)
                            'wind_direction' => $currentRep['D'] ?? null, // Wind direction (compass)
                            'wind_gust' => $currentRep['G'] ?? null,    // Wind gust (mph)
                            'visibility' => $currentRep['V'] ?? null,   // Visibility
                            'weather_type' => $currentRep['W'] ?? null, // Weather type code
                            'weather_description' => $this->getMetOfficeWeatherType($currentRep['W'] ?? 0),
                            'precipitation_probability' => $currentRep['Pp'] ?? null, // Precipitation probability %
                            'uv_index' => $currentRep['U'] ?? null,     // UV index
                            'timestamp' => now()
                        ];
                    }
                }
            }

        } catch (\Exception $e) {
            Log::warning('Met Office Site-Specific weather failed: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Find nearest Met Office location to given coordinates
     */
    protected function findNearestLocation($locations, $lat, $lon)
    {
        $nearest = null;
        $minDistance = PHP_FLOAT_MAX;
        
        foreach ($locations as $location) {
            $locLat = (float) $location['latitude'];
            $locLon = (float) $location['longitude'];
            
            // Simple Euclidean distance (good enough for UK)
            $distance = sqrt(pow($locLat - $lat, 2) + pow($locLon - $lon, 2));
            
            if ($distance < $minDistance) {
                $minDistance = $distance;
                $nearest = $location;
            }
        }
        
        return $nearest;
    }

    /**
     * Convert Met Office weather type code to description
     */
    protected function getMetOfficeWeatherType($code)
    {
        $types = [
            0 => 'Clear night',
            1 => 'Sunny day',
            2 => 'Partly cloudy (night)',
            3 => 'Partly cloudy (day)',
            4 => 'Not used',
            5 => 'Mist',
            6 => 'Fog',
            7 => 'Cloudy',
            8 => 'Overcast',
            9 => 'Light rain shower (night)',
            10 => 'Light rain shower (day)',
            11 => 'Drizzle',
            12 => 'Light rain',
            13 => 'Heavy rain shower (night)',
            14 => 'Heavy rain shower (day)',
            15 => 'Heavy rain',
            16 => 'Sleet shower (night)',
            17 => 'Sleet shower (day)',
            18 => 'Sleet',
            19 => 'Hail shower (night)',
            20 => 'Hail shower (day)',
            21 => 'Hail',
            22 => 'Light snow shower (night)',
            23 => 'Light snow shower (day)',
            24 => 'Light snow',
            25 => 'Heavy snow shower (night)',
            26 => 'Heavy snow shower (day)',
            27 => 'Heavy snow',
            28 => 'Thunder shower (night)',
            29 => 'Thunder shower (day)',
            30 => 'Thunder'
        ];
        
        return $types[$code] ?? 'Unknown';
    }

    /**
     * WeatherAPI.com current weather implementation
     */
    protected function getWeatherApiCurrentWeather()
    {
        try {
            $response = Http::timeout(10)->get('https://api.weatherapi.com/v1/current.json', [
                'key' => $this->weatherApiKey,
                'q' => "{$this->farmLatitude},{$this->farmLongitude}",
                'aqi' => 'yes' // Include air quality data
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                $weather = [
                    'source' => 'weatherapi',
                    'location' => $data['location']['name'] ?? null,
                    'region' => $data['location']['region'] ?? null,
                    'temperature' => $data['current']['temp_c'] ?? null,
                    'feels_like' => $data['current']['feelslike_c'] ?? null,
                    'humidity' => $data['current']['humidity'] ?? null,
                    'pressure' => $data['current']['pressure_mb'] ?? null,
                    'wind_speed' => $data['current']['wind_kph'] ?? null,
                    'wind_speed_mph' => $data['current']['wind_mph'] ?? null,
                    'wind_direction' => $data['current']['wind_degree'] ?? null,
                    'wind_dir' => $data['current']['wind_dir'] ?? null,
                    'wind_gust_kph' => $data['current']['gust_kph'] ?? null,
                    'visibility' => $data['current']['vis_km'] ?? null,
                    'description' => $data['current']['condition']['text'] ?? null,
                    'weather_description' => $data['current']['condition']['text'] ?? null,
                    'weather_icon' => $data['current']['condition']['icon'] ?? null,
                    'weather_code' => $data['current']['condition']['code'] ?? null,
                    'uv_index' => $data['current']['uv'] ?? null,
                    'cloud_cover' => $data['current']['cloud'] ?? null,
                    'precipitation_mm' => $data['current']['precip_mm'] ?? null,
                    'timestamp' => now(),
                    'last_updated' => $data['current']['last_updated'] ?? null,
                ];
                
                // Add air quality if available (Pro Plus feature)
                if (isset($data['current']['air_quality'])) {
                    $weather['air_quality'] = [
                        'us_epa_index' => $data['current']['air_quality']['us-epa-index'] ?? null,
                        'gb_defra_index' => $data['current']['air_quality']['gb-defra-index'] ?? null,
                        'pm2_5' => $data['current']['air_quality']['pm2_5'] ?? null,
                        'pm10' => $data['current']['air_quality']['pm10'] ?? null,
                        'co' => $data['current']['air_quality']['co'] ?? null,
                        'no2' => $data['current']['air_quality']['no2'] ?? null,
                        'o3' => $data['current']['air_quality']['o3'] ?? null,
                        'so2' => $data['current']['air_quality']['so2'] ?? null,
                    ];
                }
                
                return $weather;
            }
            
        } catch (\Exception $e) {
            Log::error('WeatherAPI.com current weather failed: ' . $e->getMessage());
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
                    'description' => $data['weather'][0]['description'] ?? null,
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
     * WeatherAPI.com forecast implementation
     */
    protected function getWeatherApiForecast($days = 5)
    {
        try {
            $response = Http::timeout(10)->get('http://api.weatherapi.com/v1/forecast.json', [
                'key' => $this->weatherApiKey,
                'q' => "{$this->farmLatitude},{$this->farmLongitude}",
                'days' => $days,
                'aqi' => 'no',
                'alerts' => 'no'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $forecastDays = [];
                
                foreach ($data['forecast']['forecastday'] ?? [] as $day) {
                    $forecastDays[] = [
                        'date' => $day['date'],
                        'temp' => [
                            'min' => $day['day']['mintemp_c'] ?? 0,
                            'max' => $day['day']['maxtemp_c'] ?? 0
                        ],
                        'rain' => $day['day']['totalprecip_mm'] ?? 0,
                        'wind_speed' => $day['day']['maxwind_kph'] ?? 0,
                        'humidity' => $day['day']['avghumidity'] ?? 0,
                        'condition' => $day['day']['condition']['text'] ?? '',
                        'icon' => $day['day']['condition']['icon'] ?? ''
                    ];
                }
                
                return $forecastDays;
            }
            
        } catch (\Exception $e) {
            Log::error('WeatherAPI.com forecast failed: ' . $e->getMessage());
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
            // Get location list first
            $locationResponse = Http::timeout(10)->withHeaders([
                'apikey' => $this->metOfficeApiKey,
                'accept' => 'application/json'
            ])->get('https://data.hub.api.metoffice.gov.uk/sitespecific/v0/site/list');

            if (!$locationResponse->successful()) {
                return null;
            }

            // Find nearest location
            $locations = $locationResponse->json()['Locations']['Location'] ?? [];
            $nearestLocation = $this->findNearestLocation($locations, $this->farmLatitude, $this->farmLongitude);
            
            if (!$nearestLocation) {
                return null;
            }

            // Get daily forecast
            $response = Http::timeout(10)->withHeaders([
                'apikey' => $this->metOfficeApiKey,
                'accept' => 'application/json'
            ])->get("https://data.hub.api.metoffice.gov.uk/sitespecific/v0/site/{$nearestLocation['id']}", [
                'res' => 'daily'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                $dailyForecasts = [];
                $periods = $data['SiteRep']['DV']['Location']['Period'] ?? [];
                
                foreach (array_slice($periods, 0, $days) as $period) {
                    $rep = $period['Rep'][0] ?? null;
                    if ($rep) {
                        $dailyForecasts[] = [
                            'date' => substr($period['value'], 0, 10),
                            'temp' => [
                                'min' => $rep['Nm'] ?? 0,  // Night minimum temp
                                'max' => $rep['Dm'] ?? 0   // Day maximum temp
                            ],
                            'rain' => $rep['PPd'] ?? 0,    // Precipitation probability
                            'wind_speed' => $rep['S'] ?? 0,
                            'humidity' => $rep['Hn'] ?? 0,
                            'condition' => $this->getMetOfficeWeatherType($rep['W'] ?? 0)
                        ];
                    }
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

    /**
     * Met Office Site-Specific forecast implementation
     */
    protected function getMetOfficeSiteSpecificForecast($days = 5)
    {
        try {
            // Get location list first
            $locationResponse = Http::timeout(10)->withHeaders([
                'apikey' => $this->metOfficeSiteSpecificKey,
                'accept' => 'application/json'
            ])->get('https://data.hub.api.metoffice.gov.uk/sitespecific/v0/site/list');

            if (!$locationResponse->successful()) {
                Log::warning('Failed to get Met Office locations for forecast: ' . $locationResponse->status());
                return null;
            }

            // Find nearest location
            $locations = $locationResponse->json()['Locations']['Location'] ?? [];
            $nearestLocation = $this->findNearestLocation($locations, $this->farmLatitude, $this->farmLongitude);
            
            if (!$nearestLocation) {
                return null;
            }

            // Get daily forecast
            $response = Http::timeout(10)->withHeaders([
                'apikey' => $this->metOfficeSiteSpecificKey,
                'accept' => 'application/json'
            ])->get("https://data.hub.api.metoffice.gov.uk/sitespecific/v0/site/{$nearestLocation['id']}", [
                'res' => 'daily'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                $dailyForecasts = [];
                $periods = $data['SiteRep']['DV']['Location']['Period'] ?? [];
                
                foreach (array_slice($periods, 0, $days) as $period) {
                    $rep = $period['Rep'][0] ?? null;
                    if ($rep) {
                        $dailyForecasts[] = [
                            'date' => substr($period['value'], 0, 10),
                            'temp' => [
                                'min' => $rep['Nm'] ?? 0,
                                'max' => $rep['Dm'] ?? 0
                            ],
                            'rain' => $rep['PPd'] ?? 0,
                            'wind_speed' => $rep['S'] ?? 0,
                            'humidity' => $rep['Hn'] ?? 0,
                            'condition' => $this->getMetOfficeWeatherType($rep['W'] ?? 0)
                        ];
                    }
                }
                
                return $dailyForecasts;
            } else {
                Log::warning('Met Office Site-Specific forecast API failed with status: ' . $response->status() . ', body: ' . $response->body());
            }

        } catch (\Exception $e) {
            Log::warning('Met Office Site-Specific forecast failed: ' . $e->getMessage());
        }

        return null;
    }
}
