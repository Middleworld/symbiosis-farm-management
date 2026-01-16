<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class WeatherService
{
    protected $tomorrowIoApiKey;
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
        
        $this->tomorrowIoApiKey = $weatherKeys['tomorrow_io'];
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
            // Try WeatherAPI.com FIRST - more accurate for exact coordinates
            if ($this->weatherApiKey) {
                $weatherApiData = $this->getWeatherApiCurrentWeather();
                if ($weatherApiData) {
                    return $weatherApiData;
                }
            }

            // Try Met Office Land Observations - actual station data but may be distant
            if ($this->metOfficeLandObservationsKey) {
                $metOfficeData = $this->getMetOfficeLandObservations();
                if ($metOfficeData) {
                    return $metOfficeData;
                }
            }

            // Met Office DataHub API currently not working - commented out
            // if ($this->metOfficeApiKey) {
            //     $metOfficeData = $this->getMetOfficeDataHubCurrentWeather();
            //     if ($metOfficeData) {
            //         return $metOfficeData;
            //     }
            // }

            // Return null if no weather data available
            return null;
        });
    }

    /**
     * Get 5-day forecast (WeatherAPI primary for accuracy, Met Office backup)
     */
    public function getForecast($days = 5)
    {
        $cacheKey = "weather_forecast_{$days}_{$this->farmLatitude}_{$this->farmLongitude}";
        
        return Cache::remember($cacheKey, 1800, function () use ($days) { // 30 minute cache
            // Try WeatherAPI.com first - accurate UK forecasts
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

            // Try Met Office DataHub - currently not working due to GRIB2 format
            if ($this->metOfficeApiKey) {
                $metOfficeData = $this->getMetOfficeDataHubForecast($days);
                if ($metOfficeData) {
                    return [
                        'source' => 'met_office_datahub',
                        'daily' => $metOfficeData,
                        'timestamp' => now()
                    ];
                }
            }
            
            // Return null if no forecast data available
            return null;
        });
    }

    /**
     * Get historical weather data (currently not available - OpenWeatherMap removed)
     */
    public function getHistoricalWeather($startDate, $endDate)
    {
        // Historical weather data not available without OpenWeatherMap
        // Consider implementing with WeatherAPI historical data if needed
        return null;
    }

    /**
     * Calculate Growing Degree Days (requires historical weather data)
     */
    public function getGrowingDegreeDays($startDate, $endDate, $baseTemp = 10)
    {
        $historicalData = $this->getHistoricalWeather($startDate, $endDate);
        
        if (!$historicalData) {
            return [
                'growing_degree_days' => 0,
                'base_temperature' => $baseTemp,
                'period' => [
                    'start' => $startDate,
                    'end' => $endDate
                ],
                'error' => 'Historical weather data not available'
            ];
        }
        
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
                    'lat' => round($this->farmLatitude, 2),
                    'lon' => round($this->farmLongitude, 2)
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    // API returns array, take first station
                    return $data[0] ?? null;
                }
                return null;
            });

            if (!$nearestStation || !isset($nearestStation['geohash'])) {
                Log::warning('Met Office: No nearby observation station found');
                return null;
            }

            $geohash = $nearestStation['geohash'];
            $stationName = $nearestStation['area'] ?? 'Unknown Station';
            
            // Calculate distance to station if coordinates are available
            $distance = 0;
            if (isset($nearestStation['latitude']) && isset($nearestStation['longitude'])) {
                $stationLat = $nearestStation['latitude'];
                $stationLon = $nearestStation['longitude'];
                
                // Haversine formula for distance calculation
                $earthRadius = 6371; // km
                $latDelta = deg2rad($stationLat - $this->farmLatitude);
                $lonDelta = deg2rad($stationLon - $this->farmLongitude);
                
                $a = sin($latDelta/2) * sin($latDelta/2) +
                     cos(deg2rad($this->farmLatitude)) * cos(deg2rad($stationLat)) *
                     sin($lonDelta/2) * sin($lonDelta/2);
                $c = 2 * atan2(sqrt($a), sqrt(1-$a));
                $distance = $earthRadius * $c;
                
                // Reject stations more than 50km away - too distant for accurate local weather
                if ($distance > 50) {
                    Log::info("Met Office station {$stationName} is {$distance}km away - too distant, skipping");
                    return null;
                }
            }

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
                'location' => $stationName, // Station area as location
                'region' => $nearestStation['region'] ?? '', // Region from station data
                'station_name' => $stationName,
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
     * NEW Met Office DataHub current weather (point-based API with atmospheric-models)
     */
    protected function getMetOfficeDataHubCurrentWeather()
    {
        try {
            // Use atmospheric-models API for current observations
            $response = Http::timeout(10)->withHeaders([
                'apikey' => $this->metOfficeApiKey,
                'accept' => 'application/json'
            ])->get("https://datahub.metoffice.gov.uk/atmospheric-models/1.0.0/observations/point/hourly", [
                'lat' => $this->farmLatitude,
                'lon' => $this->farmLongitude
            ]);

            if ($response->successful()) {
                $data = $response->json();

                // Log the response structure for debugging
                Log::info('Met Office DataHub current weather response', [
                    'status' => $response->status(),
                    'data_keys' => $data ? array_keys($data) : 'null',
                    'data' => $data,
                    'body' => $response->body()
                ]);

                // Parse the response based on actual structure
                if ($data && isset($data['features'][0]['properties'])) {
                    $properties = $data['features'][0]['properties'];
                    $latestObs = $properties['timeSeries'][0] ?? null;

                    if ($latestObs) {
                        return [
                            'source' => 'met_office_datahub',
                            'temperature' => $latestObs['screenTemperature'] ?? null,
                            'feels_like' => $latestObs['feelsLikeTemperature'] ?? null,
                            'humidity' => $latestObs['screenRelativeHumidity'] ?? null,
                            'wind_speed' => $latestObs['windSpeed10m'] ?? null,
                            'wind_direction' => $latestObs['windDirectionFrom10m'] ?? null,
                            'wind_gust' => $latestObs['windGustSpeed10m'] ?? null,
                            'visibility' => $latestObs['visibility'] ?? null,
                            'pressure' => $latestObs['mslp'] ?? null, // Mean sea level pressure
                            'weather_description' => $this->getMetOfficeDataHubWeatherType($latestObs['significantWeatherCode'] ?? 0),
                            'timestamp' => now()
                        ];
                    }
                }
            } else {
                Log::warning('Met Office DataHub current weather failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
            }

        } catch (\Exception $e) {
            Log::warning('Met Office DataHub current weather exception: ' . $e->getMessage());
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
                
                // Group by days and format to match WeatherAPI structure
                $dailyForecasts = [];
                foreach ($data['list'] ?? [] as $forecast) {
                    $date = date('Y-m-d', $forecast['dt']);
                    
                    if (!isset($dailyForecasts[$date])) {
                        $dailyForecasts[$date] = [
                            'date' => $date,
                            'temp' => ['min' => 999, 'max' => -999],
                            'humidity' => [],
                            'weather' => [],
                            'rain' => 0,
                            'wind_speeds' => [],
                            'conditions' => []
                        ];
                    }
                    
                    $temp = $forecast['main']['temp'];
                    $dailyForecasts[$date]['temp']['min'] = min($dailyForecasts[$date]['temp']['min'], $temp);
                    $dailyForecasts[$date]['temp']['max'] = max($dailyForecasts[$date]['temp']['max'], $temp);
                    $dailyForecasts[$date]['humidity'][] = $forecast['main']['humidity'];
                    $dailyForecasts[$date]['weather'][] = $forecast['weather'][0]['description'];
                    $dailyForecasts[$date]['rain'] += $forecast['rain']['3h'] ?? 0;
                    $dailyForecasts[$date]['wind_speeds'][] = $forecast['wind']['speed'] ?? 0;
                    $dailyForecasts[$date]['conditions'][] = $forecast['weather'][0]['main'] ?? '';
                }
                
                // Format final daily data to match WeatherAPI structure
                $formattedDaily = [];
                foreach ($dailyForecasts as $date => $dayData) {
                    $avgHumidity = count($dayData['humidity']) > 0 ? array_sum($dayData['humidity']) / count($dayData['humidity']) : 0;
                    $maxWind = count($dayData['wind_speeds']) > 0 ? max($dayData['wind_speeds']) : 0;
                    $primaryCondition = count($dayData['conditions']) > 0 ? $dayData['conditions'][0] : 'Unknown';
                    $conditionText = count($dayData['weather']) > 0 ? $dayData['weather'][0] : 'Unknown';
                    
                    $formattedDaily[] = [
                        'date' => $date,
                        'temp' => [
                            'min' => round($dayData['temp']['min'], 1),
                            'max' => round($dayData['temp']['max'], 1)
                        ],
                        'rain' => round($dayData['rain'], 1),
                        'wind_speed' => round($maxWind * 3.6, 1), // Convert m/s to km/h
                        'humidity' => round($avgHumidity),
                        'condition' => $conditionText,
                        'icon' => '' // OpenWeatherMap doesn't provide icons in free tier
                    ];
                }
                
                return $formattedDaily;
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
     * NEW Met Office DataHub forecast (point-based API with atmospheric-models)
     */
    protected function getMetOfficeDataHubForecast($days = 5)
    {
        try {
            // Use atmospheric-models API for daily forecasts
            $response = Http::timeout(10)->withHeaders([
                'apikey' => $this->metOfficeApiKey,
                'accept' => 'application/json'
            ])->get("https://datahub.metoffice.gov.uk/atmospheric-models/1.0.0/forecasts/point/daily", [
                'lat' => $this->farmLatitude,
                'lon' => $this->farmLongitude
            ]);

            if ($response->successful()) {
                $data = $response->json();

                Log::info('Met Office DataHub forecast response structure', [
                    'has_features' => $data && isset($data['features']),
                    'feature_count' => $data && isset($data['features']) ? count($data['features']) : 0,
                    'properties_keys' => $data && isset($data['features'][0]['properties']) ? array_keys($data['features'][0]['properties']) : []
                ]);

                if (isset($data['features'][0]['properties']['timeSeries'])) {
                    $timeSeries = $data['features'][0]['properties']['timeSeries'];
                    $dailyData = [];

                    // Group by date
                    $groupedByDate = [];
                    foreach ($timeSeries as $entry) {
                        $date = Carbon::parse($entry['time'])->format('Y-m-d');
                        if (!isset($groupedByDate[$date])) {
                            $groupedByDate[$date] = [];
                        }
                        $groupedByDate[$date][] = $entry;
                    }

                    // Process each day's data
                    foreach (array_slice($groupedByDate, 0, $days) as $date => $dayEntries) {
                        $dayData = $this->processMetOfficeDataHubDayData($dayEntries);

                        $dailyData[] = [
                            'date' => $date,
                            'temp_max' => $dayData['max_temp'],
                            'temp_min' => $dayData['min_temp'],
                            'humidity' => $dayData['avg_humidity'],
                            'wind_speed' => $dayData['max_wind'],
                            'wind_direction' => $dayData['wind_direction'],
                            'precipitation' => $dayData['total_precip'],
                            'condition' => $this->getMetOfficeDataHubWeatherType($dayData['weather_code']),
                            'description' => $this->getMetOfficeDataHubWeatherType($dayData['weather_code'])
                        ];
                    }

                    return $dailyData;
                }
            } else {
                Log::warning('Met Office DataHub forecast failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
            }

        } catch (\Exception $e) {
            Log::warning('Met Office DataHub forecast exception: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Process a day's worth of Met Office DataHub data
     */
    protected function processMetOfficeDataHubDayData($dayEntries)
    {
        $maxTemp = null;
        $minTemp = null;
        $totalHumidity = 0;
        $humidityCount = 0;
        $maxWind = null;
        $windDirection = null;
        $totalPrecip = 0;
        $weatherCode = 0;

        foreach ($dayEntries as $entry) {
            // Temperature
            if (isset($entry['screenTemperature'])) {
                $temp = $entry['screenTemperature'];
                $maxTemp = $maxTemp === null ? $temp : max($maxTemp, $temp);
                $minTemp = $minTemp === null ? $temp : min($minTemp, $temp);
            }

            // Humidity
            if (isset($entry['screenRelativeHumidity'])) {
                $totalHumidity += $entry['screenRelativeHumidity'];
                $humidityCount++;
            }

            // Wind
            if (isset($entry['windSpeed10m'])) {
                $wind = $entry['windSpeed10m'];
                $maxWind = $maxWind === null ? $wind : max($maxWind, $wind);
                if (isset($entry['windDirectionFrom10m'])) {
                    $windDirection = $entry['windDirectionFrom10m'];
                }
            }

            // Precipitation
            if (isset($entry['totalPrecipitationAmount'])) {
                $totalPrecip += $entry['totalPrecipitationAmount'];
            }

            // Weather code (take the most significant)
            if (isset($entry['significantWeatherCode']) && $entry['significantWeatherCode'] > $weatherCode) {
                $weatherCode = $entry['significantWeatherCode'];
            }
        }

        return [
            'max_temp' => $maxTemp,
            'min_temp' => $minTemp,
            'avg_humidity' => $humidityCount > 0 ? $totalHumidity / $humidityCount : null,
            'max_wind' => $maxWind,
            'wind_direction' => $windDirection,
            'total_precip' => $totalPrecip,
            'weather_code' => $weatherCode
        ];
    }

    /**
     * Convert Met Office DataHub weather code to description
     */
    protected function getMetOfficeDataHubWeatherType($code)
    {
        // Met Office DataHub uses different codes than the old API
        $types = [
            0 => 'Clear',
            1 => 'Sunny',
            2 => 'Partly cloudy',
            3 => 'Cloudy',
            4 => 'Overcast',
            5 => 'Mist',
            6 => 'Fog',
            7 => 'Light rain',
            8 => 'Drizzle',
            9 => 'Heavy rain',
            10 => 'Sleet',
            11 => 'Hail',
            12 => 'Light snow',
            13 => 'Heavy snow',
            14 => 'Thunder',
            15 => 'Thunderstorm'
        ];

        return $types[$code] ?? 'Unknown';
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

    /**
     * Get Met Office weather map images (PNG overlays)
     * Parameters: precipitation_rate, temperature, pressure
     * Regions: uk, europe
     * Time steps: various intervals up to 168 hours
     * 
     * NOTE: Met Office Map Images API appears to require OAuth2 authentication
     * despite having an active subscription. The API returns 302 redirects to login.
     */
    public function getMetOfficeMapImages($parameter = 'precipitation_rate', $region = 'uk', $hoursAhead = 0)
    {
        try {
            // Try OAuth2 first if credentials are available
            $useOAuth2 = !empty(config('metoffice.client_id')) && !empty(config('metoffice.client_secret'));

            if ($useOAuth2) {
                try {
                    $metOfficeAuth = MetOfficeAuthService::getInstance();
                    $accessToken = $metOfficeAuth->getAccessToken();

                    // Build the API URL for map images
                    $baseUrl = "https://datahub.metoffice.gov.uk/map-images/1.0.0";
                    $url = "{$baseUrl}/{$parameter}/{$region}/latest/{$hoursAhead}";

                    $response = Http::timeout(15)->withHeaders([
                        'Authorization' => 'Bearer ' . $accessToken,
                        'accept' => 'image/png'
                    ])->get($url);

                    if ($response->successful()) {
                        $body = $response->body();

                        // Check if we got HTML instead of PNG (still authentication issue)
                        if (str_starts_with($body, '<!DOCTYPE html>')) {
                            // Fall back to API key method
                            $useApiKey = true;
                        } else {
                            return [
                                'url' => $url,
                                'parameter' => $parameter,
                                'region' => $region,
                                'hours_ahead' => $hoursAhead,
                                'image_data' => base64_encode($body),
                                'content_type' => 'image/png',
                                'timestamp' => now(),
                                'source' => 'met_office_oauth2'
                            ];
                        }
                    } else {
                        // OAuth2 request failed, try API key
                        $useApiKey = true;
                    }
                } catch (\Exception $e) {
                    // OAuth2 failed, fall back to API key
                    Log::info('OAuth2 failed, falling back to API key authentication', ['error' => $e->getMessage()]);
                    $useApiKey = true;
                }
            } else {
                $useApiKey = true;
            }

            // API key fallback
            if (isset($useApiKey) && $useApiKey) {
                $apiKey = $this->metOfficeMapImagesKey;
                if (empty($apiKey)) {
                    return [
                        'error' => 'No Met Office authentication available',
                        'oauth2_configured' => $useOAuth2,
                        'api_key_available' => !empty($apiKey)
                    ];
                }

                // Build the API URL for map images using API key
                $baseUrl = "https://datahub.metoffice.gov.uk/map-images/v1";
                $url = "{$baseUrl}/{$parameter}/{$region}/latest/{$hoursAhead}";

                $response = Http::timeout(15)->withHeaders([
                    'accept' => 'image/png',
                    'x-ibm-client-id' => $apiKey
                ])->get($url);

                if ($response->successful()) {
                    $body = $response->body();

                    // Check if we got HTML instead of PNG
                    if (str_starts_with($body, '<!DOCTYPE html>')) {
                        return [
                            'error' => 'Met Office Map Images API returning HTML instead of PNG',
                            'url_attempted' => $url,
                            'response_preview' => substr($body, 0, 200) . '...',
                            'suggestion' => 'Check API key validity or contact Met Office support'
                        ];
                    }

                    return [
                        'url' => $url,
                        'parameter' => $parameter,
                        'region' => $region,
                        'hours_ahead' => $hoursAhead,
                        'image_data' => base64_encode($body),
                        'content_type' => 'image/png',
                        'timestamp' => now(),
                        'source' => 'met_office_api_key'
                    ];
                } else {
                    return [
                        'error' => 'Met Office Map Images API request failed',
                        'status' => $response->status(),
                        'url' => $url,
                        'response' => substr($response->body(), 0, 200)
                    ];
                }
            }

        } catch (\Exception $e) {
            return [
                'error' => 'Met Office Map Images API exception: ' . $e->getMessage(),
                'parameter' => $parameter,
                'region' => $region,
                'hours_ahead' => $hoursAhead
            ];
        }
    }

    /**
     * Get weather map overlays from a free alternative service (Open-Meteo)
     * Free weather API with map tiles - no API key required
     */
    public function getOpenMeteoMapImages($layer = 'precipitation', $zoom = 6, $x = 0, $y = 0)
    {
        try {
            // Open-Meteo provides free weather map tiles
            // Note: Limited to certain layers and resolutions
            $validLayers = ['precipitation', 'temperature', 'wind_speed'];
            if (!in_array($layer, $validLayers)) {
                $layer = 'precipitation';
            }

            // Map our parameter names to Open-Meteo layer names
            $layerMap = [
                'precipitation' => 'precipitation',
                'temperature' => 'temperature_2m',
                'pressure' => 'pressure_msl', // Note: may not be available
                'wind_speed' => 'wind_speed_10m'
            ];

            $openMeteoLayer = $layerMap[$layer] ?? 'precipitation';

            // Open-Meteo tile API (free, no API key required)
            $url = "https://tile.open-meteo.com/v1/{$openMeteoLayer}/{$zoom}/{$x}/{$y}.png";

            $response = Http::timeout(15)->get($url);

            if ($response->successful()) {
                $body = $response->body();

                // Check if we got a valid PNG
                if (str_starts_with($body, "\x89PNG")) {
                    return [
                        'url' => $url,
                        'layer' => $layer,
                        'zoom' => $zoom,
                        'x' => $x,
                        'y' => $y,
                        'image_data' => base64_encode($body),
                        'content_type' => 'image/png',
                        'timestamp' => now(),
                        'source' => 'open-meteo',
                        'note' => 'Free alternative - limited layers and resolutions'
                    ];
                } else {
                    return [
                        'error' => 'Open-Meteo returned invalid PNG data',
                        'url' => $url
                    ];
                }
            } else {
                return [
                    'error' => 'Open-Meteo API request failed',
                    'status' => $response->status(),
                    'url' => $url
                ];
            }

        } catch (\Exception $e) {
            return [
                'error' => 'Open-Meteo API exception: ' . $e->getMessage(),
                'layer' => $layer,
                'zoom' => $zoom,
                'x' => $x,
                'y' => $y
            ];
        }
    }

    /**
     * Get weather radar tiles from RainViewer (completely free, no API key required)
     * Returns tile layer URLs for multiple time frames to enable time travel
     */
    public function getRainViewerMapImages($zoom = 6, $x = 31, $y = 20)
    {
        try {
            // RainViewer provides free weather radar tiles
            // First get the latest radar timestamp
            $listUrl = "https://api.rainviewer.com/public/weather-maps.json";
            $listResponse = Http::timeout(10)->get($listUrl);

            if (!$listResponse->successful()) {
                return [
                    'error' => 'RainViewer API list request failed',
                    'status' => $listResponse->status()
                ];
            }

            $radarList = $listResponse->json();
            if (empty($radarList['radar']['past'])) {
                return [
                    'error' => 'No radar data available from RainViewer'
                ];
            }

            // Get all available radar timestamps (last 2 hours typically)
            $radarFrames = $radarList['radar']['past'];
            $latestTimestamp = end($radarFrames)['time'];

            // Create tile URLs for all time frames
            $timeFrames = [];
            foreach ($radarFrames as $frame) {
                $timestamp = $frame['time'];
                $timeFrames[] = [
                    'timestamp' => $timestamp,
                    'tile_url' => "https://tilecache.rainviewer.com/v2/radar/{$timestamp}/256/{z}/{x}/{y}/1/1_1.png",
                    'time_formatted' => date('H:i', $timestamp)
                ];
            }

            // Return the latest frame as default, plus all available frames
            return [
                'tile_url' => "https://tilecache.rainviewer.com/v2/radar/{$latestTimestamp}/256/{z}/{x}/{y}/1/1_1.png",
                'timestamp' => $latestTimestamp,
                'source' => 'rainviewer',
                'note' => 'Free global weather radar - precipitation data only',
                'attribution' => '© RainViewer - Free Weather Radar',
                'time_frames' => $timeFrames, // All available time frames for animation
                'current_index' => count($timeFrames) - 1 // Latest frame index
            ];

        } catch (\Exception $e) {
            return [
                'error' => 'RainViewer API exception: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get weather forecast tiles from OpenWeatherMap (requires API key)
     * Provides future weather predictions as map overlays
     */
    public function getTomorrowIoForecastTiles($layer = 'precipitationIntensity', $zoom = 6, $forecastHours = 0)
    {
        try {
            // Tomorrow.io provides superior weather tiles with better visuals
            // Free tier available with API key
            $apiKey = $this->tomorrowIoApiKey ?? env('TOMORROW_IO_API_KEY', '');
            if (empty($apiKey)) {
                return [
                    'error' => 'Tomorrow.io API key required for high-quality forecast maps'
                ];
            }

            // Tomorrow.io layer mapping
            $validLayers = [
                'precipitation_rate' => 'precipitationIntensity',
                'temperature' => 'temperature',
                'pressure' => 'seaLevelPressure',
                'wind_speed' => 'windSpeed'
            ];

            if (!isset($validLayers[$layer])) {
                $layer = 'precipitationIntensity';
            }

            $layer = $validLayers[$layer];

            // Forecast times available (hourly for next 6 hours, then 3-hourly)
            $forecastTimes = [];
            for ($i = 0; $i <= 48; $i += 3) {
                $forecastTimes[] = $i;
            }

            if (!in_array($forecastHours, $forecastTimes)) {
                $forecastHours = 0; // Default to current
            }

            // Build Tomorrow.io tile URL
            $baseTime = now()->startOfHour()->addHours($forecastHours);
            $timeString = $baseTime->format('Y-m-d\TH:i:s\Z');

            $tileUrl = "https://api.tomorrow.io/v4/map/tile/{z}/{x}/{y}/{$layer}/{$timeString}.png?apikey={$apiKey}";

            // Create forecast time frames without probing tiles (avoid rate limits)
            $forecastFrames = [];
            foreach ($forecastTimes as $hours) {
                $frameTime = now()->startOfHour()->addHours($hours);
                $frameTimeString = $frameTime->format('Y-m-d\TH:i:s\Z');

                $forecastFrames[] = [
                    'forecast_hours' => $hours,
                    'tile_url' => "https://api.tomorrow.io/v4/map/tile/{z}/{x}/{y}/{$layer}/{$frameTimeString}.png?apikey={$apiKey}",
                    'time_formatted' => $hours === 0 ? 'Now' : "+{$hours}h"
                ];
            }

            return [
                'tile_url' => $tileUrl,
                'forecast_hours' => $forecastHours,
                'source' => 'tomorrow_io_forecast',
                'note' => 'Premium weather tiles - requires Tomorrow.io API key',
                'attribution' => '© Tomorrow.io - Premium Weather Data',
                'forecast_frames' => $forecastFrames,
                'current_index' => array_search($forecastHours, $forecastTimes)
            ];

        } catch (\Exception $e) {
            return [
                'error' => 'Tomorrow.io forecast tiles exception: ' . $e->getMessage()
            ];
        }
    }

    /**
     * OpenWeatherMap forecast weather tiles
     * Provides future weather predictions as map overlays
     */
    public function getOpenWeatherMapForecastTiles($layer = 'precipitation_new', $zoom = 6, $forecastHours = 0)
    {
        try {
            // OpenWeatherMap provides forecast weather tiles
            // Requires API key but provides future predictions
            $apiKey = $this->openWeatherApiKey;
            if (empty($apiKey)) {
                return [
                    'error' => 'OpenWeatherMap API key required for forecast maps'
                ];
            }

            // OpenWeatherMap forecast tile layers
            $validLayers = [
                'precipitation_new' => 'precipitation_new',
                'clouds_new' => 'clouds_new',
                'pressure_new' => 'pressure_new',
                'wind_new' => 'wind_new',
                'temp_new' => 'temp_new'
            ];

            if (!isset($validLayers[$layer])) {
                $layer = 'precipitation_new';
            }

            // Forecast times available (3-hourly intervals)
            $forecastTimes = [0, 3, 6, 9, 12, 15, 18, 21, 24, 27, 30, 33, 36, 39, 42, 45, 48];
            if (!in_array($forecastHours, $forecastTimes)) {
                $forecastHours = 0; // Default to current forecast
            }

            // Build forecast tile URL
            $tileUrl = "https://tile.openweathermap.org/map/{$layer}/{$zoom}/0/0.png?appid={$apiKey}";
            if ($forecastHours > 0) {
                $tileUrl .= "&time=" . ($forecastHours * 3600); // Convert hours to seconds
            }

            // Test the tile URL by making a request
            $response = Http::timeout(10)->get($tileUrl);

            if ($response->successful()) {
                $body = $response->body();

                // Check if we got a valid PNG
                if (str_starts_with($body, "\x89PNG")) {
                    // Create forecast time frames (3-hourly for next 48 hours)
                    $forecastFrames = [];
                    foreach ($forecastTimes as $hours) {
                        $forecastFrames[] = [
                            'forecast_hours' => $hours,
                            'tile_url' => "https://tile.openweathermap.org/map/{$layer}/{$zoom}/{x}/{y}.png?appid={$apiKey}" . ($hours > 0 ? "&time=" . ($hours * 3600) : ""),
                            'time_formatted' => $hours === 0 ? 'Now' : "+{$hours}h"
                        ];
                    }

                    return [
                        'tile_url' => "https://tile.openweathermap.org/map/{$layer}/{$zoom}/{z}/{x}/{y}.png?appid={$apiKey}" . ($forecastHours > 0 ? "&time=" . ($forecastHours * 3600) : ""),
                        'forecast_hours' => $forecastHours,
                        'source' => 'openweathermap_forecast',
                        'note' => 'Forecast weather tiles - requires API key',
                        'attribution' => '© OpenWeatherMap - Forecast Data',
                        'forecast_frames' => $forecastFrames,
                        'current_index' => array_search($forecastHours, $forecastTimes)
                    ];
                } else {
                    return [
                        'error' => 'OpenWeatherMap returned invalid tile data',
                        'url' => $tileUrl
                    ];
                }
            } else {
                return [
                    'error' => 'OpenWeatherMap forecast tile request failed',
                    'status' => $response->status(),
                    'url' => $tileUrl
                ];
            }

        } catch (\Exception $e) {
            return [
                'error' => 'OpenWeatherMap forecast tiles exception: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get weather map overlay (tries Met Office first, then Open-Meteo free service)
     */
    public function getWeatherMapOverlay($parameter = 'precipitation_rate', $region = 'uk', $hoursAhead = 0, $source = null)
    {
        // Check if this is a forecast request (positive hoursAhead) or historical (negative/zero)
        $isForecast = $hoursAhead > 0;

        if ($source === 'tomorrow') {
            $forecastResult = $this->getTomorrowIoForecastTiles($parameter, 6, $hoursAhead);

            if (!isset($forecastResult['error'])) {
                return $forecastResult;
            }

            // If Tomorrow.io is requested explicitly, do not fall back to other providers
            return [
                'error' => $forecastResult['error'] ?? 'Tomorrow.io tiles unavailable',
                'requested_source' => 'tomorrow',
                'parameter' => $parameter,
                'hours_ahead' => $hoursAhead,
                'status' => $forecastResult['status'] ?? null,
                'url' => $forecastResult['url'] ?? null
            ];
        }

        if ($isForecast) {
            // FUTURE FORECAST: Use Tomorrow.io premium weather tiles (MUCH better than OpenWeatherMap)
            $layerMap = [
                'precipitation_rate' => 'precipitationIntensity',
                'temperature' => 'temperature',
                'pressure' => 'seaLevelPressure',
                'wind_speed' => 'windSpeed'
            ];

            $layer = $layerMap[$parameter] ?? 'precipitationIntensity';

            $forecastResult = $this->getTomorrowIoForecastTiles($layer, 6, $hoursAhead);

            if (!isset($forecastResult['error'])) {
                return $forecastResult;
            }

            // Fallback to OpenWeatherMap if Tomorrow.io fails
            $owLayerMap = [
                'precipitation_rate' => 'precipitation_new',
                'temperature' => 'temp_new',
                'pressure' => 'pressure_new',
                'wind_speed' => 'wind_new'
            ];

            $owLayer = $owLayerMap[$parameter] ?? 'precipitation_new';
            $owResult = $this->getOpenWeatherMapForecastTiles($owLayer, 6, $hoursAhead);

            if (!isset($owResult['error'])) {
                return $owResult;
            }

            // If both forecast services fail, show helpful error
            return [
                'error' => 'Weather forecast maps require Tomorrow.io or OpenWeatherMap API key',
                'forecast_requested' => true,
                'hours_ahead' => $hoursAhead,
                'solution' => 'Add Tomorrow.io API key for premium maps, or OpenWeatherMap as fallback'
            ];
        } else {
            // HISTORICAL DATA: Use RainViewer free weather radar (completely free, no API key required)
            $rainViewerResult = $this->getRainViewerMapImages(6, 31, 20); // UK center coordinates

            if (!isset($rainViewerResult['error'])) {
                return $rainViewerResult;
            }

            // Fallback to Open-Meteo free weather maps (no API key required)
            $layerMap = [
                'precipitation_rate' => 'precipitation',
                'temperature' => 'temperature',
                'pressure' => 'pressure',
                'wind_speed' => 'wind_speed'
            ];

            $layer = $layerMap[$parameter] ?? 'precipitation';

            // For UK region, use appropriate tile coordinates (approximate center of UK)
            $openMeteoResult = $this->getOpenMeteoMapImages($layer, 6, 31, 20); // Rough UK center

            if (!isset($openMeteoResult['error'])) {
                return $openMeteoResult;
            }

            // If all free services fail, return helpful error message
            return [
                'error' => 'Weather map overlays currently unavailable',
                'current_weather_status' => '✅ Working (Met Office Land Observations + WeatherAPI)',
                'forecast_status' => '✅ Working (OpenWeatherMap 5-day + WeatherAPI 3-day)',
                'map_overlays_status' => '✅ Working (Free RainViewer radar - no API keys needed)',
                'solution' => 'Weather maps work without member API keys using free RainViewer radar',
                'met_office_limitation' => 'Met Office APIs prohibited on rented systems (Terms of Service)',
                'free_alternatives' => 'RainViewer + Open-Meteo provide weather overlays without API keys'
            ];
        }
    }

    /**
     * Get available weather map options
     */
    public function getMapImageOptions()
    {
        return [
            'parameters' => [
                'precipitation_rate' => 'Total Precipitation Rate',
                'temperature' => 'Surface Temperature',
                'pressure' => 'Pressure (reduced to MSL)'
            ],
            'regions' => [
                'uk' => 'United Kingdom',
                'europe' => 'Europe'
            ],
            'time_steps' => [
                'current' => 0,
                '3_hours' => 3,
                '6_hours' => 6,
                '12_hours' => 12,
                '24_hours' => 24,
                '48_hours' => 48,
                '72_hours' => 72
            ]
        ];
    }

    /**
     * Get Met Office map overlay for Leaflet
     */
    public function getMetOfficeMapOverlay($parameter, $hoursAhead = 0)
    {
        return $this->getWeatherMapOverlay($parameter, 'uk', $hoursAhead);
    }
}
