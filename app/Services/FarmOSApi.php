<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Carbon\Carbon;
use App\Services\FarmOSAuthService;

/**
 * FarmOS API Service (New Version)
 * Integrates with FarmOS using centralized authentication patterns
 */
class FarmOSApi
{
    private $client;
    private $baseUrl;
    private $token;

    public function __construct()
    {
        $this->baseUrl = Config::get('farmos.url', 'https://farmos.middleworldfarms.org');
        
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => 30,
            'headers' => [
                'Accept' => 'application/vnd.api+json',
                'Content-Type' => 'application/vnd.api+json',
            ]
        ]);
    }

    /**
     * Authenticate with FarmOS using centralized auth service
     */
    public function authenticate()
    {
        try {
            $authService = FarmOSAuthService::getInstance();
            $token = $authService->getAccessToken();
            if ($token) {
                $this->token = $token;
                Log::info('FarmOS OAuth2 authentication successful (centralized)');
                return true;
            }
            
            throw new \Exception('Failed to get OAuth2 token from auth service');
        } catch (\Exception $e) {
            Log::error('FarmOS authentication failed: ' . $e->getMessage());
            throw new \Exception('FarmOS authentication failed: ' . $e->getMessage());
        }
    }

    /**
     * Get authentication headers using centralized auth service
     */
    public function getAuthHeaders()
    {
        try {
            $authService = FarmOSAuthService::getInstance();
            return $authService->getAuthHeaders();
        } catch (\Exception $e) {
            Log::warning('Failed to get auth headers: ' . $e->getMessage());
            return ['Accept' => 'application/vnd.api+json'];
        }
    }

    /**
     * Check if authenticated using centralized auth service
     */
    public function isAuthenticated()
    {
        try {
            $authService = FarmOSAuthService::getInstance();
            return $authService->isAuthenticated();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get available crop types and varieties from farmOS taxonomy
     */
    public function getAvailableCropTypes()
    {
        try {
            // Cache crop types for 30 minutes - they don't change often
            $cacheKey = 'farmos.crop.types.v1';
            
            return Cache::remember($cacheKey, now()->addMinutes(30), function () {
                $this->authenticate();
                
                $cropData = [
                    'types' => [],
                    'varieties' => []
                ];

                // Get plant types from farmOS taxonomy using centralized auth
                $headers = $this->getAuthHeaders();
                $response = $this->client->get('/api/taxonomy_term/plant_type', [
                    'headers' => $headers
                ]);

                $data = json_decode($response->getBody(), true);

                if (isset($data['data'])) {
                    foreach ($data['data'] as $term) {
                        $attributes = $term['attributes'] ?? [];
                        $name = $attributes['name'] ?? 'Unknown';
                        
                        if ($name !== 'Unknown') {
                        $cropData['types'][] = [
                            'id' => $term['id'] ?? '',
                            'name' => $name,
                            'label' => ucfirst(strtolower($name))
                        ];
                    }
                }
            }

            // Get crop varieties using pagination
            try {
                $varieties = $this->getVarieties();
                
                foreach ($varieties as $variety) {
                    $attributes = $variety['attributes'] ?? [];
                    $name = $attributes['name'] ?? '';
                    $description = $attributes['description']['value'] ?? '';
                    $parent = $variety['relationships']['parent']['data'][0]['id'] ?? null;
                    
                    if ($name) {
                        $cropData['varieties'][] = [
                            'id' => $variety['id'] ?? '',
                            'name' => $name,
                            'label' => $name,
                            'description' => $description,
                            'parent_id' => $parent,
                            'crop_type' => $parent  // Add crop_type field for frontend compatibility
                        ];
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Could not fetch crop varieties: ' . $e->getMessage());
            }

            // Add fallback if no types found
            if (empty($cropData['types'])) {
                $defaultTypes = ['lettuce', 'tomato', 'carrot', 'cabbage', 'potato', 'spinach', 'kale', 'radish', 'beets', 'arugula'];
                foreach ($defaultTypes as $type) {
                    $cropData['types'][] = [
                        'id' => $type,
                        'name' => $type,
                        'label' => ucfirst($type)
                    ];
                }
            }

            // Sort alphabetically
            usort($cropData['types'], function($a, $b) {
                return strcmp($a['label'], $b['label']);
            });
            
            usort($cropData['varieties'], function($a, $b) {
                return strcmp($a['label'], $b['label']);
            });

            return $cropData;
            }); // End of Cache::remember callback

        } catch (\Exception $e) {
            Log::error('Failed to fetch crop types from farmOS: ' . $e->getMessage());
            
            // Fallback data
            return [
                'types' => [
                    ['id' => 'lettuce', 'name' => 'lettuce', 'label' => 'Lettuce'],
                    ['id' => 'carrot', 'name' => 'carrot', 'label' => 'Carrot'],
                    ['id' => 'radish', 'name' => 'radish', 'label' => 'Radish'],
                    ['id' => 'spinach', 'name' => 'spinach', 'label' => 'Spinach'],
                    ['id' => 'kale', 'name' => 'kale', 'label' => 'Kale'],
                    ['id' => 'arugula', 'name' => 'arugula', 'label' => 'Arugula'],
                    ['id' => 'beets', 'name' => 'beets', 'label' => 'Beets']
                ],
                'varieties' => []
            ];
        }
    }

    /**
     * Get varieties with proper pagination
     * Note: FarmOS uses 'plant_type' vocabulary (not plant_variety) for all 2,959+ varieties
     */
    public function getVarieties()
    {
        return $this->jsonApiPaginatedFetch('/api/taxonomy_term/plant_type');
    }

    /**
     * Get a single variety by ID
     */
    public function getVarietyById($varietyId)
    {
        try {
            $headers = $this->getAuthHeaders();
            $response = $this->client->get("/api/taxonomy_term/plant_variety/{$varietyId}", [
                'headers' => $headers,
                'http_errors' => false
            ]);

            if ($response->getStatusCode() === 200) {
                $data = json_decode($response->getBody()->getContents(), true);
                return $data['data'] ?? null;
            }

            Log::warning('Failed to fetch variety by ID', [
                'id' => $varietyId,
                'status' => $response->getStatusCode()
            ]);
            return null;
        } catch (\Exception $e) {
            Log::error('Error fetching variety by ID', [
                'id' => $varietyId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get plant types with proper pagination
     */
    public function getPlantTypes()
    {
        return $this->jsonApiPaginatedFetch('/api/taxonomy_term/plant_type');
    }

    /**
     * Generic JSON:API GET with pagination
     */
    private function jsonApiPaginatedFetch($path, $params = [], $maxPages = 200, $pageSize = 50)
    {
        $results = [];
        $page = 0;
        $retried = false;
        
        do {
            $query = array_merge($params, [
                'page[limit]' => $pageSize,
                'page[offset]' => $page * $pageSize
            ]);
            $resp = $this->jsonApiRequest($path, $query);
            
            if ($resp['status'] === 401 && !$retried) {
                // Clear auth cache and retry once
                Cache::forget('farmos_access_token');
                $this->authenticate();
                $retried = true;
                continue;
            }
            
            if ($resp['status'] !== 200) {
                Log::warning('FarmOS API pagination failed', [
                    'path' => $path,
                    'page' => $page,
                    'status' => $resp['status']
                ]);
                break;
            }
            
            $dataChunk = $resp['body']['data'] ?? [];
            $results = array_merge($results, $dataChunk);
            
            // Check if there's a next page link
            $hasNextPage = isset($resp['body']['links']['next']);
            
            // Stop if no next page or if we got no data
            if (!$hasNextPage || count($dataChunk) === 0) {
                break;
            }
            
            $page++;
            $retried = false;
        } while ($page < $maxPages);
        
        if ($page >= $maxPages) {
            Log::warning('FarmOS API pagination hit max pages limit', [
                'path' => $path,
                'maxPages' => $maxPages,
                'totalFetched' => count($results)
            ]);
        }
        
        return $results;
    }

    /**
     * Make JSON:API request with centralized auth
     */
    private function jsonApiRequest($path, $query = [])
    {
        $this->authenticate();
        $headers = $this->getAuthHeaders();
        $options = ['headers' => $headers, 'http_errors' => false];
        
        if (!empty($query)) {
            $options['query'] = $query;
        }
        
        $response = $this->client->get($path, $options);
        $status = $response->getStatusCode();
        $body = json_decode($response->getBody(), true);
        
        return ['status' => $status, 'body' => $body];
    }

    /**
     * Get geometry assets (land/fields) for mapping
     */
    public function getGeometryAssets($options = [])
    {
        try {
            $cacheKey = 'farmos.geometry.assets.v1';
            $forceRefresh = $options['refresh'] ?? false;
            
            if (!$forceRefresh) {
                $cached = Cache::get($cacheKey);
                if ($cached) {
                    Log::info('FarmOS geometry assets cache hit', ['feature_count' => count($cached['features'])]);
                    return $cached;
                }
            }

            if (!$this->authenticate()) {
                Log::warning('FarmOS authentication failed');
                return [
                    'type' => 'FeatureCollection',
                    'features' => [],
                    'error' => 'Authentication failed - check farmOS credentials'
                ];
            }

            $result = $this->fetchGeometryAssetsInternal();
            
            if (!isset($result['error'])) {
                Cache::put($cacheKey, $result, now()->addMinutes(10));
            }
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error('Failed to load geometry assets: ' . $e->getMessage());
            return [
                'type' => 'FeatureCollection',
                'features' => [],
                'error' => 'Exception: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Internal geometry assets fetch
     */
    private function fetchGeometryAssetsInternal()
    {
        try {
            $headers = $this->getAuthHeaders();
            $requestOptions = ['headers' => $headers, 'http_errors' => false];
            $requestOptions['query'] = ['filter[status]' => 'active'];
            
            $response = $this->client->get('/api/asset/land', $requestOptions);
            $status = $response->getStatusCode();
            
            if ($status === 401 || $status === 403) {
                return [
                    'type' => 'FeatureCollection', 
                    'features' => [], 
                    'error' => 'Unauthorized'
                ];
            }
            
            $data = json_decode($response->getBody(), true);
            $features = [];
            
            if (isset($data['data']) && is_array($data['data'])) {
                foreach ($data['data'] as $asset) {
                    if (isset($asset['attributes']['geometry'])) {
                        $geometry = $this->convertWktToGeoJson($asset['attributes']['geometry']);
                        if ($geometry) {
                            $features[] = [
                                'type' => 'Feature',
                                'properties' => [
                                    'name' => $asset['attributes']['name'] ?? 'Unnamed Area',
                                    'id' => $asset['id'],
                                    'status' => $asset['attributes']['status'] ?? 'unknown',
                                    'land_type' => $asset['attributes']['land_type'] ?? 'field',
                                ],
                                'geometry' => $geometry
                            ];
                        }
                    }
                }
            }
            
            return ['type' => 'FeatureCollection', 'features' => $features];
            
        } catch (\Exception $e) {
            return [
                'type' => 'FeatureCollection', 
                'features' => [], 
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Simple WKT to GeoJSON conversion
     */
    private function convertWktToGeoJson($geometryData)
    {
        if (!isset($geometryData['value']) || !isset($geometryData['geo_type'])) {
            return null;
        }
        
        $wkt = $geometryData['value'];
        $geoType = $geometryData['geo_type'];
        
        if (strtoupper($geoType) === 'POLYGON') {
            return $this->parsePolygonWkt($wkt);
        }
        
        return null;
    }

    /**
     * Parse POLYGON WKT to GeoJSON
     */
    private function parsePolygonWkt($wkt)
    {
        $wkt = trim($wkt);
        if (preg_match('/^POLYGON\s*\(\((.*)\)\)$/i', $wkt, $matches)) {
            $coordinateString = $matches[1];
            $coordinates = $this->parseCoordinateString($coordinateString);
            
            return [
                'type' => 'Polygon',
                'coordinates' => [$coordinates]
            ];
        }
        return null;
    }

    /**
     * Parse coordinate string into array of [lon, lat] pairs
     */
    private function parseCoordinateString($coordinateString)
    {
        $coordinates = [];
        $pairs = explode(',', $coordinateString);
        
        foreach ($pairs as $pair) {
            $coords = preg_split('/\s+/', trim($pair));
            if (count($coords) >= 2) {
                $coordinates[] = [(float)$coords[0], (float)$coords[1]];
            }
        }
        
        return $coordinates;
    }

    /**
     * Get crop planning data
     */
    public function getCropPlanningData()
    {
        try {
            // Cache crop planning data for 15 minutes - it updates frequently from field work
            $cacheKey = 'farmos.crop.planning.data.v1';
            
            return Cache::remember($cacheKey, now()->addMinutes(15), function () {
                if (!$this->authenticate()) {
                    return [];
                }

                // Simple implementation - get plant assets
                $headers = $this->getAuthHeaders();
                $response = $this->client->get('/api/asset/plant', [
                    'headers' => $headers
                ]);

                $data = json_decode($response->getBody(), true);
                $cropPlans = [];

                if (isset($data['data'])) {
                    foreach ($data['data'] as $plant) {
                        $attributes = $plant['attributes'] ?? [];
                        
                        $cropPlans[] = [
                            'farmos_asset_id' => $plant['id'],
                            'crop_type' => 'vegetable',
                            'variety' => $attributes['name'] ?? '',
                            'status' => $attributes['status'] ?? 'active',
                            'created_at' => $attributes['created'] ?? date('c'),
                            'updated_at' => $attributes['changed'] ?? date('c'),
                        ];
                    }
                }

                return $cropPlans;
            }); // End of Cache::remember callback
            
        } catch (\Exception $e) {
            Log::error('FarmOS crop planning data fetch failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Clear planting chart related caches
     * Call this after creating/updating plants, logs, or crop plans
     */
    public function clearPlantingChartCache()
    {
        Cache::forget('farmos.crop.planning.data.v1');
        Cache::forget('farmos.geometry.assets.v1');
        Log::info('Cleared planting chart cache');
    }

    /**
     * Clear crop types cache
     * Call this after creating/updating crop types or varieties
     */
    public function clearCropTypesCache()
    {
        Cache::forget('farmos.crop.types.v1');
        Log::info('Cleared crop types cache');
    }

    /**
     * Clear all FarmOS caches
     * Use sparingly - only when major data changes occur
     */
    public function clearAllCaches()
    {
        $this->clearPlantingChartCache();
        $this->clearCropTypesCache();
        Log::info('Cleared all FarmOS caches');
    }

    /**
     * Create crop plan in farmOS
     */
    public function createCropPlan($planData)
    {
        $this->authenticate();
        
        $data = [
            'data' => [
                'type' => 'plan--crop',
                'attributes' => [
                    'name' => $planData['crop']['name'] . ' - ' . $planData['type'] . ' Plan',
                    'notes' => [
                        'value' => $planData['notes'] ?? '',
                        'format' => 'default'
                    ],
                    'status' => $planData['status'] ?? 'pending'
                ]
            ]
        ];

        try {
            $headers = $this->getAuthHeaders();
            $response = $this->client->post('/api/plan/crop', [
                'headers' => $headers,
                'json' => $data
            ]);

            $result = json_decode($response->getBody(), true);
            Log::info('Created farmOS crop plan', [
                'crop' => $planData['crop']['name'],
                'type' => $planData['type'],
                'plan_id' => $result['data']['id'] ?? 'unknown'
            ]);

            // Clear cache when new crop plan is created
            $this->clearPlantingChartCache();

            return $result;
            
        } catch (\Exception $e) {
            Log::error('Failed to create farmOS crop plan: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get crop plans from farmOS
     * 
     * @param array $filters Optional filters (e.g., ['name' => 'Season Configuration'])
     * @return array
     */
    public function getCropPlans($filters = [])
    {
        try {
            $this->authenticate();
            $headers = $this->getAuthHeaders();
            
            $query = [];
            
            // Add name filter if provided
            if (isset($filters['name'])) {
                $query['filter[name][operator]'] = 'CONTAINS';
                $query['filter[name][value]'] = $filters['name'];
            }
            
            // Add status filter if provided
            if (isset($filters['status'])) {
                $query['filter[status]'] = $filters['status'];
            }
            
            $response = $this->client->get('/api/plan/crop', [
                'headers' => $headers,
                'query' => $query
            ]);

            $data = json_decode($response->getBody(), true);
            return $data['data'] ?? [];
            
        } catch (\Exception $e) {
            Log::error('Failed to fetch crop plans: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Update an existing crop plan in farmOS
     * 
     * @param string $planId The UUID of the plan to update
     * @param array $planData Updated plan data
     * @return array
     */
    public function updateCropPlan($planId, $planData)
    {
        try {
            $this->authenticate();
            $headers = $this->getAuthHeaders();
            
            $data = [
                'data' => [
                    'type' => 'plan--crop',
                    'id' => $planId,
                    'attributes' => []
                ]
            ];
            
            // Add attributes if provided
            if (isset($planData['name'])) {
                $data['data']['attributes']['name'] = $planData['name'];
            }
            
            if (isset($planData['notes'])) {
                $data['data']['attributes']['notes'] = [
                    'value' => $planData['notes'],
                    'format' => 'default'
                ];
            }
            
            if (isset($planData['status'])) {
                $data['data']['attributes']['status'] = $planData['status'];
            }
            
            $response = $this->client->patch("/api/plan/crop/{$planId}", [
                'headers' => $headers,
                'json' => $data,
                'http_errors' => false
            ]);

            $statusCode = $response->getStatusCode();
            $result = json_decode($response->getBody(), true);

            if ($statusCode >= 200 && $statusCode < 300) {
                Log::info('Updated farmOS crop plan', [
                    'plan_id' => $planId,
                    'status' => $statusCode
                ]);
                
                // Clear cache when plan is updated
                $this->clearPlantingChartCache();
                
                return $result;
            } else {
                Log::error('Failed to update farmOS crop plan', [
                    'plan_id' => $planId,
                    'status' => $statusCode,
                    'response' => $result
                ]);
                throw new \Exception('Failed to update plan: HTTP ' . $statusCode);
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to update farmOS crop plan: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get harvest logs from farmOS
     */
    public function getHarvestLogs($since = null)
    {
        try {
            $this->authenticate();
            $headers = $this->getAuthHeaders();
            
            $query = ['filter[status]' => 'done'];
            if ($since) {
                $query['filter[timestamp][value]'] = $since;
                $query['filter[timestamp][operator]'] = '>=';
            }
            
            $response = $this->client->get('/api/log/harvest', [
                'headers' => $headers,
                'query' => $query
            ]);

            $data = json_decode($response->getBody(), true);
            return $data['data'] ?? [];
            
        } catch (\Exception $e) {
            Log::error('Failed to fetch harvest logs: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get available locations from farmOS
     */
    public function getAvailableLocations()
    {
        try {
            $this->authenticate();
            $headers = $this->getAuthHeaders();
            
            $locations = [];
            $nextUrl = '/api/asset/land?filter[status]=active';
            
            // Fetch all pages of locations
            while ($nextUrl) {
                $response = $this->client->get($nextUrl, [
                    'headers' => $headers
                ]);

                $data = json_decode($response->getBody(), true);
                
                if (isset($data['data'])) {
                    foreach ($data['data'] as $asset) {
                        $locations[] = [
                            'id' => $asset['id'],
                            'name' => $asset['attributes']['name'] ?? 'Unnamed Location',
                            'label' => $asset['attributes']['name'] ?? 'Unnamed Location'
                        ];
                    }
                }
                
                // Check for next page
                $nextUrl = $data['links']['next']['href'] ?? null;
                
                // If nextUrl is a full URL, extract just the path + query
                if ($nextUrl && strpos($nextUrl, 'http') === 0) {
                    $parsed = parse_url($nextUrl);
                    $nextUrl = $parsed['path'] . (isset($parsed['query']) ? '?' . $parsed['query'] : '');
                }
            }
            
            Log::info("📍 Fetched all locations from FarmOS", ['total_count' => count($locations)]);
            
            return $locations;
            
        } catch (\Exception $e) {
            Log::error('Failed to fetch available locations: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get file from FarmOS by file ID
     */
    public function getFileById(string $fileId)
    {
        try {
            $this->authenticate();
            $headers = $this->getAuthHeaders();
            
            // First, get the file entity to get the actual file URL
            $response = $this->client->get("/api/file/file/{$fileId}", [
                'headers' => $headers
            ]);

            $data = json_decode($response->getBody(), true);
            
            if (!isset($data['data'])) {
                Log::warning('File entity not found', ['file_id' => $fileId]);
                return null;
            }

            $fileData = $data['data'];
            $fileUrl = $fileData['attributes']['uri']['url'] ?? null;
            
            if (!$fileUrl) {
                Log::warning('No file URL in response', ['file_id' => $fileId, 'data' => $fileData]);
                return null;
            }

            // Download the actual image file
            $imageResponse = $this->client->get($fileUrl, [
                'headers' => $headers
            ]);

            return [
                'content' => $imageResponse->getBody()->getContents(),
                'mime_type' => $fileData['attributes']['filemime'] ?? 'image/jpeg',
                'filename' => $fileData['attributes']['filename'] ?? 'variety-image.jpg'
            ];
            
        } catch (\Exception $e) {
            Log::error('Failed to fetch file from FarmOS: ' . $e->getMessage(), [
                'file_id' => $fileId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Public wrapper for fetching paginated data (for debugging/admin tools)
     */
    public function fetchPaginatedData($path, $params = [])
    {
        return $this->jsonApiPaginatedFetch($path, $params);
    }

    /**
     * Get bed occupancy data for timeline visualization
     * Returns beds and plantings within the specified date range
     */
    public function getBedOccupancy($startDate, $endDate)
    {
        try {
            $this->authenticate();
            $headers = $this->getAuthHeaders();

            // Fetch all beds (land assets) using pagination
            $bedsData = $this->jsonApiPaginatedFetch('/api/asset/land', ['filter[status]' => 'active']);
            $beds = [];

            foreach ($bedsData as $bed) {
                $bedName = $bed['attributes']['name'] ?? 'Unnamed Bed';

                // Skip beds that are just block names without specific bed numbers
                if (preg_match('/^block\s+\d+$/i', $bedName)) {
                    continue;
                }

                // Try to extract block information from bed name
                $block = 'Block Unknown';
                if (preg_match('/block\s*(\d+)/i', $bedName, $matches)) {
                    $block = 'Block ' . $matches[1];
                } elseif (preg_match('/(\d+)\s*\/\s*\d+/', $bedName, $matches)) {
                    $block = 'Block ' . $matches[1];
                } elseif (preg_match('/^(\d+)/', $bedName, $matches)) {
                    $block = 'Block ' . $matches[1];
                }

                $beds[] = [
                    'id' => $bed['id'],
                    'name' => $bedName,
                    'block' => $block,
                    'status' => $bed['attributes']['status'] ?? 'active',
                    'land_type' => $bed['attributes']['land_type'] ?? 'bed',
                    'geometry' => $bed['attributes']['geometry'] ?? null,
                    'archived' => $bed['attributes']['status'] === 'archived'
                ];
            }

            // Fetch all plant/transplant logs to find when plants moved to beds
            // Don't filter by status - we want all transplant logs
            $transplantLogs = $this->jsonApiPaginatedFetch('/api/log/transplanting', []);
            
            // Fetch all seeding logs to track planting start
            $seedingLogs = $this->jsonApiPaginatedFetch('/api/log/seeding', []);
            
            // Fetch all harvest logs
            $harvestLogs = $this->jsonApiPaginatedFetch('/api/log/harvest', []);
            
            // Index seeding logs by asset ID for quick lookup
            $seedingByAsset = [];
            foreach ($seedingLogs as $seedingLog) {
                if (isset($seedingLog['relationships']['asset']['data'][0]['id'])) {
                    $assetId = $seedingLog['relationships']['asset']['data'][0]['id'];
                    $timestamp = $seedingLog['attributes']['timestamp'] ?? null;
                    if ($timestamp) {
                        // Convert timestamp to Unix timestamp
                        if (is_numeric($timestamp)) {
                            $unixTimestamp = (int)$timestamp;
                        } else {
                            $unixTimestamp = strtotime($timestamp);
                        }
                        
                        if ($unixTimestamp) {
                            $seedingByAsset[$assetId] = date('Y-m-d', $unixTimestamp);
                        }
                    }
                }
            }
            
            // Index harvest logs by asset ID for quick lookup
            $harvestByAsset = [];
            foreach ($harvestLogs as $harvestLog) {
                if (isset($harvestLog['relationships']['asset']['data'][0]['id'])) {
                    $assetId = $harvestLog['relationships']['asset']['data'][0]['id'];
                    $timestamp = $harvestLog['attributes']['timestamp'] ?? null;
                    if ($timestamp) {
                        // Convert timestamp to Unix timestamp
                        // FarmOS can return either ISO 8601 strings or Unix timestamps
                        if (is_numeric($timestamp)) {
                            $unixTimestamp = (int)$timestamp;
                        } else {
                            $unixTimestamp = strtotime($timestamp);
                        }
                        
                        if ($unixTimestamp) {
                            $harvestByAsset[$assetId] = date('Y-m-d', $unixTimestamp);
                        }
                    }
                }
            }
            
            // Fetch harvest duration data from local database (succession planning)
            $harvestDurations = [];
            try {
                $cropPlans = \DB::table('crop_plans')
                    ->select('farmos_asset_id', 'harvest_duration_days', 'planned_harvest_start', 'planned_harvest_end')
                    ->whereNotNull('farmos_asset_id')
                    ->whereNotNull('harvest_duration_days')
                    ->get();
                
                foreach ($cropPlans as $plan) {
                    if ($plan->farmos_asset_id) {
                        $harvestDurations[$plan->farmos_asset_id] = [
                            'duration_days' => $plan->harvest_duration_days,
                            'planned_start' => $plan->planned_harvest_start,
                            'planned_end' => $plan->planned_harvest_end
                        ];
                    }
                }
                
                Log::info('Loaded harvest durations from local database', ['count' => count($harvestDurations)]);
            } catch (\Exception $e) {
                Log::warning('Failed to load harvest durations from database: ' . $e->getMessage());
            }
            
            // Create a lookup map from bed UUID to bed name
            $bedIdToName = [];
            foreach ($beds as $bed) {
                $bedIdToName[$bed['id']] = $bed['name'];
            }
            
            // Build plantings array from transplant logs
            $plantings = [];
            foreach ($transplantLogs as $log) {
                $logAttrs = $log['attributes'] ?? [];
                $timestamp = $logAttrs['timestamp'] ?? null;
                
                if (!$timestamp) {
                    Log::warning('Skipping transplant log - no timestamp');
                    continue;
                }
                
                // Convert timestamp to Unix timestamp
                // FarmOS can return either ISO 8601 strings or Unix timestamps
                if (is_numeric($timestamp)) {
                    $unixTimestamp = (int)$timestamp;
                } else {
                    $unixTimestamp = strtotime($timestamp);
                }
                
                if (!$unixTimestamp) {
                    Log::warning('Skipping transplant log - invalid timestamp format', ['timestamp' => $timestamp]);
                    continue;
                }
                
                $transplantDate = date('Y-m-d', $unixTimestamp);
                
                // Get the asset (plant) this log references
                $assetId = null;
                $assetName = 'Unknown Plant';
                if (isset($log['relationships']['asset']['data'][0])) {
                    $assetId = $log['relationships']['asset']['data'][0]['id'];
                    // Asset name comes from the log name typically
                    $logName = $logAttrs['name'] ?? '';
                    if (preg_match('/Transplanting:\s*(.+)/', $logName, $matches)) {
                        $assetName = trim($matches[1]);
                    } else {
                        $assetName = $logName;
                    }
                }
                
                // Get the bed (location) this log references
                $bedId = null;
                if (isset($log['relationships']['location']['data'][0])) {
                    $bedId = $log['relationships']['location']['data'][0]['id'];
                }
                
                // Only add if we have both asset and bed
                if ($assetId && $bedId) {
                    // Check if this asset has a harvest date and seeding date
                    $harvestDate = $harvestByAsset[$assetId] ?? null;
                    $seedingDate = $seedingByAsset[$assetId] ?? null;
                    
                    // Calculate harvest end date using duration from local database
                    $harvestEndDate = null;
                    if ($harvestDate) {
                        // Try to get duration from local database first
                        if (isset($harvestDurations[$assetId])) {
                            $durationDays = $harvestDurations[$assetId]['duration_days'] ?? 0;
                            if ($durationDays > 0) {
                                $harvestEndDate = date('Y-m-d', strtotime($harvestDate . ' + ' . $durationDays . ' days'));
                            } else {
                                // Fallback to planned end if no duration
                                $harvestEndDate = $harvestDurations[$assetId]['planned_end'] ?? null;
                            }
                        }
                        
                        // If still no end date, use crop-specific default harvest windows
                        if (!$harvestEndDate) {
                            $defaultHarvestDays = $this->getDefaultHarvestDuration($crop);
                            $harvestEndDate = date('Y-m-d', strtotime($harvestDate . ' + ' . $defaultHarvestDays . ' days'));
                            Log::info("Using default harvest duration for {$crop}: {$defaultHarvestDays} days");
                        }
                    }
                    
                    // Convert bed UUID to bed name for frontend
                    $bedName = $bedIdToName[$bedId] ?? $bedId;
                    
                    // Parse crop and variety from asset name (e.g., "Broccoli Cardinal" -> crop: Broccoli, variety: Cardinal)
                    $nameParts = explode(' ', $assetName, 2);
                    $crop = $nameParts[0] ?? $assetName;
                    $variety = $nameParts[1] ?? '';
                    
                    $plantings[] = [
                        'id' => $assetId,
                        'name' => $assetName,
                        'crop' => $crop,
                        'variety' => $variety,
                        'status' => 'active',
                        'seeding_date' => $seedingDate, // When it was seeded (may be in propagation)
                        'transplant_date' => $transplantDate, // When it moved to this bed
                        'harvest_date' => $harvestDate, // When harvesting window starts
                        'harvest_end_date' => $harvestEndDate, // When harvesting window ends (from duration)
                        'harvest_duration_days' => $harvestDurations[$assetId]['duration_days'] ?? null,
                        'bed_id' => $bedName, // Use singular bed_id with bed name
                        'notes' => $logAttrs['notes']['value'] ?? null,
                        // For backwards compatibility, keep start_date and end_date
                        'start_date' => $transplantDate,
                        'end_date' => $harvestEndDate ?? $harvestDate,
                    ];
                }
            }
            
            // Track which assets we've already added from transplant logs
            $processedAssets = array_column($plantings, 'id');
            
            // Add direct-seeded crops (seeding logs with bed locations that weren't transplanted)
            foreach ($seedingLogs as $log) {
                $logAttrs = $log['attributes'] ?? [];
                $timestamp = $logAttrs['timestamp'] ?? null;
                
                if (!$timestamp) {
                    continue;
                }
                
                // Get the asset (plant) this log references
                $assetId = null;
                $assetName = 'Unknown Plant';
                if (isset($log['relationships']['asset']['data'][0])) {
                    $assetId = $log['relationships']['asset']['data'][0]['id'];
                    // Skip if we already processed this asset from transplant logs
                    if (in_array($assetId, $processedAssets)) {
                        continue;
                    }
                    
                    // Get asset name from log
                    $logName = $logAttrs['name'] ?? '';
                    if (preg_match('/Seeding:\s*(.+)/', $logName, $matches)) {
                        $assetName = trim($matches[1]);
                    } else {
                        $assetName = $logName;
                    }
                }
                
                // Get the bed (location) - only process if seeded directly in a bed
                $bedId = null;
                if (isset($log['relationships']['location']['data'][0])) {
                    $bedId = $log['relationships']['location']['data'][0]['id'];
                }
                
                // Only add if seeded directly in a bed (not in propagation)
                if ($assetId && $bedId && isset($bedIdToName[$bedId])) {
                    // Convert timestamp
                    if (is_numeric($timestamp)) {
                        $unixTimestamp = (int)$timestamp;
                    } else {
                        $unixTimestamp = strtotime($timestamp);
                    }
                    
                    if (!$unixTimestamp) {
                        continue;
                    }
                    
                    $seedingDate = date('Y-m-d', $unixTimestamp);
                    $harvestDate = $harvestByAsset[$assetId] ?? null;
                    
                    // Parse crop and variety first (needed for default harvest duration)
                    $nameParts = explode(' ', $assetName, 2);
                    $crop = $nameParts[0] ?? $assetName;
                    $variety = $nameParts[1] ?? '';
                    
                    // Calculate harvest end date using duration from local database
                    $harvestEndDate = null;
                    if ($harvestDate) {
                        // Try to get duration from local database first
                        if (isset($harvestDurations[$assetId])) {
                            $durationDays = $harvestDurations[$assetId]['duration_days'] ?? 0;
                            if ($durationDays > 0) {
                                $harvestEndDate = date('Y-m-d', strtotime($harvestDate . ' + ' . $durationDays . ' days'));
                            } else {
                                // Fallback to planned end if no duration
                                $harvestEndDate = $harvestDurations[$assetId]['planned_end'] ?? null;
                            }
                        }
                        
                        // If still no end date, use crop-specific default harvest windows
                        if (!$harvestEndDate) {
                            $defaultHarvestDays = $this->getDefaultHarvestDuration($crop);
                            $harvestEndDate = date('Y-m-d', strtotime($harvestDate . ' + ' . $defaultHarvestDays . ' days'));
                            Log::info("Using default harvest duration for {$crop}: {$defaultHarvestDays} days");
                        }
                    }
                    
                    $bedName = $bedIdToName[$bedId];
                    
                    $plantings[] = [
                        'id' => $assetId,
                        'name' => $assetName,
                        'crop' => $crop,
                        'variety' => $variety,
                        'status' => 'active',
                        'seeding_date' => $seedingDate,
                        'transplant_date' => null, // Direct seeded, no transplant
                        'harvest_date' => $harvestDate,
                        'harvest_end_date' => $harvestEndDate, // When harvesting window ends (from duration)
                        'harvest_duration_days' => $harvestDurations[$assetId]['duration_days'] ?? null,
                        'bed_id' => $bedName,
                        'notes' => $logAttrs['notes']['value'] ?? null,
                        // For compatibility
                        'start_date' => $seedingDate, // For direct seeded, bed occupancy starts at seeding
                        'end_date' => $harvestEndDate ?? $harvestDate,
                        'is_direct_seeded' => true // Flag to help frontend render correctly
                    ];
                }
            }

            // Filter plantings to only include those that overlap with the requested date range
            $filteredPlantings = [];
            foreach ($plantings as $planting) {
                $plantingStart = $planting['start_date'] ?? $planting['seeding_date'] ?? null;
                $plantingEnd = $planting['end_date'] ?? $planting['harvest_end_date'] ?? $planting['harvest_date'] ?? null;
                
                // If we have date information, check for overlap
                if ($plantingStart && $plantingEnd) {
                    // Check if planting period overlaps with requested date range
                    $overlap = ($plantingStart <= $endDate) && ($plantingEnd >= $startDate);
                    if ($overlap) {
                        $filteredPlantings[] = $planting;
                    }
                } else {
                    // If no date info, include it (let frontend decide)
                    $filteredPlantings[] = $planting;
                }
            }

            Log::info('Fetched bed occupancy data', [
                'beds_count' => count($beds),
                'plantings_count' => count($filteredPlantings),
                'total_plantings_before_filter' => count($plantings),
                'transplant_logs' => count($transplantLogs),
                'harvest_logs' => count($harvestLogs),
                'seeding_logs' => count($seedingLogs),
                'date_range' => [$startDate, $endDate]
            ]);

            return [
                'beds' => $beds,
                'plantings' => $filteredPlantings
            ];

        } catch (\Exception $e) {
            Log::error('Failed to fetch bed occupancy data: ' . $e->getMessage(), [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Return empty data structure on error
            return [
                'beds' => [],
                'plantings' => []
            ];
        }
    }

    /**
     * Get default harvest duration in days based on crop type
     * These are typical harvest windows for common crops
     */
    private function getDefaultHarvestDuration($cropName)
    {
        $cropName = strtolower($cropName);
        
        // Crop-specific harvest durations (in days)
        $harvestDurations = [
            // Leafy greens - short harvest window
            'lettuce' => 14,
            'spinach' => 14,
            'arugula' => 14,
            'kale' => 21,
            'chard' => 21,
            'collards' => 21,
            
            // Root vegetables - longer harvest window
            'carrot' => 30,
            'beetroot' => 30,
            'beet' => 30,
            'radish' => 7,
            'turnip' => 21,
            'parsnip' => 45,
            
            // Brassicas - variable
            'broccoli' => 14,
            'cauliflower' => 14,
            'cabbage' => 21,
            'brussels' => 30,
            
            // Fruiting vegetables - extended harvest
            'tomato' => 60,
            'pepper' => 45,
            'cucumber' => 30,
            'zucchini' => 30,
            'squash' => 45,
            'eggplant' => 45,
            'beans' => 21,
            'peas' => 21,
            
            // Alliums
            'onion' => 14,
            'garlic' => 7,
            'leek' => 30,
            'scallion' => 14,
            
            // Herbs
            'basil' => 60,
            'parsley' => 90,
            'cilantro' => 21,
            'dill' => 30,
        ];
        
        // Check for exact match
        if (isset($harvestDurations[$cropName])) {
            return $harvestDurations[$cropName];
        }
        
        // Check for partial match (e.g., "tomato" in "cherry tomato")
        foreach ($harvestDurations as $crop => $days) {
            if (strpos($cropName, $crop) !== false) {
                return $days;
            }
        }
        
        // Default: 21 days (3 weeks) if crop not recognized
        return 21;
    }

    /**
     * Find a plant_type taxonomy term ID by name
     * Searches the 2,959 synced variety terms
     */
    private function findPlantTypeByName($name)
    {
        try {
            $this->authenticate();
            $headers = $this->getAuthHeaders();

            // Search for exact match first
            $response = $this->client->get('/api/taxonomy_term/plant_type', [
                'headers' => $headers,
                'query' => [
                    'filter[name]' => $name,
                    'page[limit]' => 1
                ],
                'http_errors' => false
            ]);

            $data = json_decode($response->getBody(), true);
            
            if (!empty($data['data'][0]['id'])) {
                return $data['data'][0]['id'];
            }

            // Try case-insensitive search
            $response = $this->client->get('/api/taxonomy_term/plant_type', [
                'headers' => $headers,
                'query' => [
                    'filter[name][operator]' => 'CONTAINS',
                    'filter[name][value]' => $name,
                    'page[limit]' => 5
                ],
                'http_errors' => false
            ]);

            $data = json_decode($response->getBody(), true);
            
            // Look for best match
            foreach ($data['data'] ?? [] as $term) {
                if (strcasecmp($term['attributes']['name'], $name) === 0) {
                    return $term['id'];
                }
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Failed to find plant_type: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Create a planting asset in FarmOS
     * Required for seeding logs - creates the asset that represents the planted crop
     */
    public function createPlantingAsset($data, $locationId = null)
    {
        try {
            $this->authenticate();
            $headers = $this->getAuthHeaders();

            // Generate planting name
            $plantingName = $data['crop_name'];
            if (isset($data['variety_name']) && $data['variety_name'] !== 'Generic') {
                $plantingName .= ' - ' . $data['variety_name'];
            }
            if (isset($data['succession_number'])) {
                $plantingName .= ' (Succession #' . $data['succession_number'] . ')';
            }

            // Prepare JSON:API payload
            $payload = [
                'data' => [
                    'type' => 'asset--plant',
                    'attributes' => [
                        'name' => $plantingName,
                        'status' => 'active',
                        'notes' => [
                            'value' => $data['notes'] ?? 'Created via AI succession planning',
                            'format' => 'default'
                        ]
                    ]
                ]
            ];

            // Look up plant_type taxonomy term
            $plantTypeId = null;
            if (isset($data['crop_name'])) {
                $searchName = $data['crop_name'];
                if (isset($data['variety_name']) && $data['variety_name'] !== 'Generic') {
                    $searchName .= ' ' . $data['variety_name'];
                }
                $plantTypeId = $this->findPlantTypeByName($searchName);
                
                if ($plantTypeId) {
                    $payload['data']['relationships']['plant_type'] = [
                        'data' => [[
                            'type' => 'taxonomy_term--plant_type',
                            'id' => $plantTypeId
                        ]]
                    ];
                } else {
                    Log::warning("Could not find plant_type for: {$searchName}");
                }
            }

            Log::info('Creating planting asset in FarmOS', [
                'name' => $plantingName,
                'payload' => $payload
            ]);

            $response = $this->client->post('/api/asset/plant', [
                'headers' => $headers,
                'json' => $payload,
                'http_errors' => false
            ]);

            $statusCode = $response->getStatusCode();
            $responseData = json_decode($response->getBody(), true);

            if ($statusCode >= 200 && $statusCode < 300) {
                $assetId = $responseData['data']['id'] ?? null;
                Log::info('Successfully created planting asset', [
                    'asset_id' => $assetId,
                    'name' => $plantingName
                ]);
                
                // Clear cache when new planting asset is created
                $this->clearPlantingChartCache();
                
                return $assetId;
            } else {
                Log::error('Failed to create planting asset', [
                    'status' => $statusCode,
                    'response' => $responseData
                ]);
                throw new \Exception('Failed to create planting asset: HTTP ' . $statusCode);
            }

        } catch (\Exception $e) {
            Log::error('Exception creating planting asset: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create a seeding log in FarmOS
     * Represents the act of seeding/planting seeds
     */
    public function createSeedingLog($logData)
    {
        try {
            $this->authenticate();
            $headers = $this->getAuthHeaders();

            // Generate log name
            $logName = 'Seeding: ' . $logData['crop_name'];
            if (isset($logData['variety_name']) && $logData['variety_name'] !== 'Generic') {
                $logName .= ' - ' . $logData['variety_name'];
            }

            // Prepare JSON:API payload
            $payload = [
                'data' => [
                    'type' => 'log--seeding',
                    'attributes' => [
                        'name' => $logName,
                        'timestamp' => strtotime($logData['timestamp']),
                        'status' => $logData['status'] ?? 'done',
                        'notes' => [
                            'value' => $logData['notes'] ?? '',
                            'format' => 'default'
                        ]
                    ],
                    'relationships' => []
                ]
            ];

            // Create quantity entity first if quantity data is provided
            if (isset($logData['quantity'])) {
                $quantityId = $this->createQuantity([
                    'measure' => 'count',
                    'value' => $logData['quantity'],
                    'unit' => $logData['quantity_unit'] ?? 'seeds',
                    'label' => ucfirst($logData['quantity_unit'] ?? 'seeds')
                ]);
                
                if ($quantityId) {
                    $payload['data']['relationships']['quantity'] = [
                        'data' => [[
                            'type' => 'quantity--standard',
                            'id' => $quantityId
                        ]]
                    ];
                }
            }

            // Add asset reference (planting)
            if (isset($logData['planting_id'])) {
                $payload['data']['relationships']['asset'] = [
                    'data' => [[
                        'type' => 'asset--plant',
                        'id' => $logData['planting_id']
                    ]]
                ];
            }

            // Add location reference
            if (isset($logData['location_id'])) {
                $payload['data']['relationships']['location'] = [
                    'data' => [[
                        'type' => 'asset--land',
                        'id' => $logData['location_id']
                    ]]
                ];
            }

            Log::info('Creating seeding log in FarmOS', [
                'name' => $logName,
                'timestamp' => $logData['timestamp']
            ]);

            $response = $this->client->post('/api/log/seeding', [
                'headers' => $headers,
                'json' => $payload,
                'http_errors' => false
            ]);

            $statusCode = $response->getStatusCode();
            $responseData = json_decode($response->getBody(), true);

            if ($statusCode >= 200 && $statusCode < 300) {
                $logId = $responseData['data']['id'] ?? null;
                Log::info('Successfully created seeding log', [
                    'log_id' => $logId,
                    'name' => $logName
                ]);
                
                // Clear cache when new seeding log is created
                $this->clearPlantingChartCache();
                
                return [
                    'success' => true,
                    'log_id' => $logId,
                    'message' => 'Seeding log created successfully'
                ];
            } else {
                Log::error('Failed to create seeding log', [
                    'status' => $statusCode,
                    'response' => $responseData
                ]);
                return [
                    'success' => false,
                    'error' => 'HTTP ' . $statusCode . ': ' . ($responseData['errors'][0]['detail'] ?? 'Unknown error')
                ];
            }

        } catch (\Exception $e) {
            Log::error('Exception creating seeding log: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Create a transplanting log in FarmOS
     * Represents moving plants from one location to another
     */
    public function createTransplantingLog($logData)
    {
        try {
            $this->authenticate();
            $headers = $this->getAuthHeaders();

            // Generate log name
            $logName = 'Transplanting: ' . $logData['crop_name'];
            if (isset($logData['variety_name']) && $logData['variety_name'] !== 'Generic') {
                $logName .= ' - ' . $logData['variety_name'];
            }

            // Prepare JSON:API payload
            $payload = [
                'data' => [
                    'type' => 'log--transplanting',
                    'attributes' => [
                        'name' => $logName,
                        'timestamp' => strtotime($logData['timestamp']),
                        'status' => $logData['status'] ?? 'done',
                        'is_movement' => $logData['is_movement'] ?? true,
                        'notes' => [
                            'value' => $logData['notes'] ?? '',
                            'format' => 'default'
                        ]
                    ],
                    'relationships' => []
                ]
            ];

            // Create quantity entity first if quantity data is provided
            if (isset($logData['quantity'])) {
                $quantityId = $this->createQuantity([
                    'measure' => 'count',
                    'value' => $logData['quantity'],
                    'unit' => $logData['quantity_unit'] ?? 'plants',
                    'label' => ucfirst($logData['quantity_unit'] ?? 'plants')
                ]);
                
                if ($quantityId) {
                    $payload['data']['relationships']['quantity'] = [
                        'data' => [[
                            'type' => 'quantity--standard',
                            'id' => $quantityId
                        ]]
                    ];
                }
            }

            // Add asset reference (planting)
            if (isset($logData['planting_id'])) {
                $payload['data']['relationships']['asset'] = [
                    'data' => [[
                        'type' => 'asset--plant',
                        'id' => $logData['planting_id']
                    ]]
                ];
            }

            // Add destination location reference (where plants are moved TO)
            if (isset($logData['destination_location_id'])) {
                $payload['data']['relationships']['location'] = [
                    'data' => [[
                        'type' => 'asset--land',
                        'id' => $logData['destination_location_id']
                    ]]
                ];
            }

            Log::info('Creating transplanting log in FarmOS', [
                'name' => $logName,
                'timestamp' => $logData['timestamp'],
                'destination' => $logData['destination_location_id'] ?? null
            ]);

            $response = $this->client->post('/api/log/transplanting', [
                'headers' => $headers,
                'json' => $payload,
                'http_errors' => false
            ]);

            $statusCode = $response->getStatusCode();
            $responseData = json_decode($response->getBody(), true);

            if ($statusCode >= 200 && $statusCode < 300) {
                $logId = $responseData['data']['id'] ?? null;
                Log::info('Successfully created transplanting log', [
                    'log_id' => $logId,
                    'name' => $logName
                ]);
                
                // Clear cache when new transplanting log is created
                $this->clearPlantingChartCache();
                
                return [
                    'success' => true,
                    'log_id' => $logId,
                    'message' => 'Transplanting log created successfully'
                ];
            } else {
                Log::error('Failed to create transplanting log', [
                    'status' => $statusCode,
                    'response' => $responseData
                ]);
                return [
                    'success' => false,
                    'error' => 'HTTP ' . $statusCode . ': ' . ($responseData['errors'][0]['detail'] ?? 'Unknown error')
                ];
            }

        } catch (\Exception $e) {
            Log::error('Exception creating transplanting log: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Create a harvest log in FarmOS
     * Represents harvesting produce from plantings
     */
    public function createHarvestLog($logData)
    {
        try {
            $this->authenticate();
            $headers = $this->getAuthHeaders();

            // Generate log name
            $logName = 'Harvest: ' . $logData['crop_name'];
            if (isset($logData['variety_name']) && $logData['variety_name'] !== 'Generic') {
                $logName .= ' - ' . $logData['variety_name'];
            }

            // Prepare JSON:API payload
            $payload = [
                'data' => [
                    'type' => 'log--harvest',
                    'attributes' => [
                        'name' => $logName,
                        'timestamp' => strtotime($logData['timestamp']),
                        'status' => $logData['status'] ?? 'done',
                        'notes' => [
                            'value' => $logData['notes'] ?? '',
                            'format' => 'default'
                        ]
                    ],
                    'relationships' => []
                ]
            ];

            // Create quantity entity first if quantity data is provided
            if (isset($logData['quantity'])) {
                $quantityId = $this->createQuantity([
                    'measure' => 'weight',
                    'value' => $logData['quantity'],
                    'unit' => $logData['quantity_unit'] ?? 'kilograms',
                    'label' => ucfirst($logData['quantity_unit'] ?? 'kilograms')
                ]);
                
                if ($quantityId) {
                    $payload['data']['relationships']['quantity'] = [
                        'data' => [[
                            'type' => 'quantity--standard',
                            'id' => $quantityId
                        ]]
                    ];
                }
            }

            // Add asset reference (planting being harvested)
            if (isset($logData['planting_id'])) {
                $payload['data']['relationships']['asset'] = [
                    'data' => [[
                        'type' => 'asset--plant',
                        'id' => $logData['planting_id']
                    ]]
                ];
            }

            // Add location reference
            if (isset($logData['location_id'])) {
                $payload['data']['relationships']['location'] = [
                    'data' => [[
                        'type' => 'asset--land',
                        'id' => $logData['location_id']
                    ]]
                ];
            }

            Log::info('Creating harvest log in FarmOS', [
                'name' => $logName,
                'timestamp' => $logData['timestamp']
            ]);

            $response = $this->client->post('/api/log/harvest', [
                'headers' => $headers,
                'json' => $payload,
                'http_errors' => false
            ]);

            $statusCode = $response->getStatusCode();
            $responseData = json_decode($response->getBody(), true);

            if ($statusCode >= 200 && $statusCode < 300) {
                $logId = $responseData['data']['id'] ?? null;
                Log::info('Successfully created harvest log', [
                    'log_id' => $logId,
                    'name' => $logName
                ]);
                
                // Clear cache when new harvest log is created
                $this->clearPlantingChartCache();
                
                return [
                    'success' => true,
                    'log_id' => $logId,
                    'message' => 'Harvest log created successfully'
                ];
            } else {
                Log::error('Failed to create harvest log', [
                    'status' => $statusCode,
                    'response' => $responseData
                ]);
                return [
                    'success' => false,
                    'error' => 'HTTP ' . $statusCode . ': ' . ($responseData['errors'][0]['detail'] ?? 'Unknown error')
                ];
            }

        } catch (\Exception $e) {
            Log::error('Exception creating harvest log: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Create a quantity entity in FarmOS
     * Quantities must be created separately and then referenced in logs
     */
    private function createQuantity($quantityData)
    {
        try {
            $this->authenticate();
            $headers = $this->getAuthHeaders();

            // Find or create the unit term
            $unitId = $this->findOrCreateUnit($quantityData['unit']);

            // Prepare JSON:API payload for quantity
            $payload = [
                'data' => [
                    'type' => 'quantity--standard',
                    'attributes' => [
                        'measure' => $quantityData['measure'] ?? 'count',
                        'value' => [
                            'numerator' => (int)$quantityData['value'],
                            'denominator' => 1
                        ],
                        'label' => $quantityData['label'] ?? ucfirst($quantityData['unit'])
                    ],
                    'relationships' => [
                        'units' => [
                            'data' => [
                                'type' => 'taxonomy_term--unit',
                                'id' => $unitId
                            ]
                        ]
                    ]
                ]
            ];

            Log::info('Creating quantity in FarmOS', [
                'measure' => $quantityData['measure'],
                'value' => $quantityData['value'],
                'unit' => $quantityData['unit']
            ]);

            $response = $this->client->post('/api/quantity/standard', [
                'headers' => $headers,
                'json' => $payload,
                'http_errors' => false
            ]);

            $statusCode = $response->getStatusCode();
            $responseData = json_decode($response->getBody(), true);

            if ($statusCode >= 200 && $statusCode < 300) {
                $quantityId = $responseData['data']['id'] ?? null;
                Log::info('Successfully created quantity', ['quantity_id' => $quantityId]);
                return $quantityId;
            } else {
                Log::error('Failed to create quantity', [
                    'status' => $statusCode,
                    'response' => $responseData
                ]);
                return null;
            }

        } catch (\Exception $e) {
            Log::error('Exception creating quantity: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Find or create a unit taxonomy term
     */
    private function findOrCreateUnit($unitName)
    {
        try {
            $this->authenticate();
            $headers = $this->getAuthHeaders();

            // Search for existing unit
            $response = $this->client->get('/api/taxonomy_term/unit', [
                'headers' => $headers,
                'query' => [
                    'filter[name]' => $unitName,
                    'page[limit]' => 1
                ],
                'http_errors' => false
            ]);

            $data = json_decode($response->getBody(), true);
            
            if (!empty($data['data'][0]['id'])) {
                return $data['data'][0]['id'];
            }

            // Create the unit if it doesn't exist
            $response = $this->client->post('/api/taxonomy_term/unit', [
                'headers' => $headers,
                'json' => [
                    'data' => [
                        'type' => 'taxonomy_term--unit',
                        'attributes' => [
                            'name' => $unitName
                        ]
                    ]
                ],
                'http_errors' => false
            ]);

            $data = json_decode($response->getBody(), true);
            return $data['data']['id'] ?? null;

        } catch (\Exception $e) {
            Log::error('Failed to find/create unit: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Update a plant type taxonomy term in FarmOS
     * Used for pushing local changes back to FarmOS (DEV MODE)
     */
    public function updatePlantTypeTerm(string $termId, array $updateData)
    {
        try {
            $this->authenticate();
            $headers = $this->getAuthHeaders();

            // Build JSON:API PATCH payload
            $payload = [
                'data' => [
                    'type' => 'taxonomy_term--plant_type',
                    'id' => $termId
                ]
            ];

            // Add attributes if provided
            if (isset($updateData['attributes']) && !empty($updateData['attributes'])) {
                $payload['data']['attributes'] = $updateData['attributes'];
            }

            // Add relationships if provided
            if (isset($updateData['relationships']) && !empty($updateData['relationships'])) {
                $payload['data']['relationships'] = $updateData['relationships'];
            }

            Log::info('Updating FarmOS plant type term', [
                'term_id' => $termId,
                'attributes' => $updateData['attributes'] ?? []
            ]);

            $response = $this->client->patch("/api/taxonomy_term/plant_type/{$termId}", [
                'headers' => $headers,
                'json' => $payload,
                'http_errors' => false
            ]);

            $statusCode = $response->getStatusCode();
            $responseData = json_decode($response->getBody(), true);

            if ($statusCode >= 200 && $statusCode < 300) {
                Log::info('Successfully updated FarmOS plant type term', [
                    'term_id' => $termId,
                    'status' => $statusCode
                ]);
                return [
                    'success' => true,
                    'status' => $statusCode,
                    'data' => $responseData
                ];
            } else {
                Log::error('Failed to update FarmOS plant type term', [
                    'term_id' => $termId,
                    'status' => $statusCode,
                    'response' => $responseData
                ]);
                return [
                    'success' => false,
                    'status' => $statusCode,
                    'error' => 'HTTP ' . $statusCode . ': ' . ($responseData['errors'][0]['detail'] ?? 'Unknown error'),
                    'body' => $responseData
                ];
            }

        } catch (\Exception $e) {
            Log::error('Exception updating FarmOS plant type term: ' . $e->getMessage(), [
                'term_id' => $termId
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
