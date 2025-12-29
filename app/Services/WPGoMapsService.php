<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WPGoMapsService
{
    private $baseUrl;
    private $apiKey;

    public function __construct()
    {
        $this->baseUrl = config('services.woocommerce.api_url', 'https://middleworldfarms.org');
        $this->apiKey = config('services.wordpress.api_key');
    }

    /**
     * Get existing maps and markers from WP Go Maps
     */
    public function getExistingMaps()
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'X-WC-API-Key' => $this->apiKey,
                    'Content-Type' => 'application/json'
                ])
                ->get($this->baseUrl . '/wp-json/wp/v2/wpgmza_maps');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'maps' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to fetch WP Go Maps data'
            ];

        } catch (\Exception $e) {
            Log::error('WP Go Maps integration failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Create a delivery route map in WP Go Maps
     */
    public function createDeliveryRouteMap($deliveries, $routeDetails, $deliveryDate)
    {
        try {
            $mapData = [
                'map_title' => 'Delivery Route - ' . date('Y-m-d', strtotime($deliveryDate)),
                'map_width' => '100',
                'map_height' => '400',
                'map_width_type' => '%',
                'map_height_type' => 'px',
                'default_marker' => '0',
                'alignment' => '1',
                'styling_enabled' => '1',
                'styling_json' => json_encode([
                    ['featureType' => 'poi', 'stylers' => [['visibility' => 'off']]]
                ]),
                'active' => '0',
                'shortcode_option' => '1'
            ];

            // Create the map
            $mapResponse = Http::timeout(30)
                ->withHeaders([
                    'X-WC-API-Key' => $this->apiKey,
                    'Content-Type' => 'application/json'
                ])
                ->post($this->baseUrl . '/wp-json/wpgmza/v1/maps', $mapData);

            if (!$mapResponse->successful()) {
                return [
                    'success' => false,
                    'error' => 'Failed to create map in WP Go Maps'
                ];
            }

            $mapId = $mapResponse->json()['id'] ?? null;
            if (!$mapId) {
                return [
                    'success' => false,
                    'error' => 'No map ID returned from WP Go Maps'
                ];
            }

            // Add depot marker
            $this->addMarkerToMap($mapId, [
                'title' => 'Middle World Farms (Depot)',
                'address' => config('services.delivery.depot_address'),
                'description' => 'Starting point for deliveries',
                'icon' => 'https://maps.google.com/mapfiles/ms/icons/green-dot.png'
            ]);

            // Add delivery markers
            foreach ($deliveries as $index => $delivery) {
                $address = $this->formatDeliveryAddress($delivery);
                if ($address) {
                    $this->addMarkerToMap($mapId, [
                        'title' => 'Stop ' . ($index + 1) . ': ' . ($delivery['name'] ?? 'Customer'),
                        'address' => $address,
                        'description' => $this->generateMarkerDescription($delivery, $index + 1),
                        'icon' => 'https://maps.google.com/mapfiles/ms/icons/blue-dot.png'
                    ]);
                }
            }

            return [
                'success' => true,
                'map_id' => $mapId,
                'shortcode' => '[wpgmza id="' . $mapId . '"]',
                'message' => 'Delivery route map created successfully'
            ];

        } catch (\Exception $e) {
            Log::error('Failed to create WP Go Maps delivery route: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Add a marker to a WP Go Maps map
     */
    private function addMarkerToMap($mapId, $markerData)
    {
        try {
            $marker = [
                'map_id' => $mapId,
                'title' => $markerData['title'],
                'address' => $markerData['address'],
                'description' => $markerData['description'] ?? '',
                'pic' => $markerData['icon'] ?? '',
                'link' => '',
                'icon' => $markerData['icon'] ?? '',
                'approved' => '1'
            ];

            $response = Http::timeout(30)
                ->withHeaders([
                    'X-WC-API-Key' => $this->apiKey,
                    'Content-Type' => 'application/json'
                ])
                ->post($this->baseUrl . '/wp-json/wpgmza/v1/markers', $marker);

            return $response->successful();

        } catch (\Exception $e) {
            Log::error('Failed to add marker to WP Go Maps: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get customer location data from WP Go Maps if available
     */
    public function getCustomerLocationData($customerEmail)
    {
        try {
            // Try to find existing markers for this customer
            $response = Http::timeout(30)
                ->withHeaders([
                    'X-WC-API-Key' => $this->apiKey,
                    'Content-Type' => 'application/json'
                ])
                ->get($this->baseUrl . '/wp-json/wpgmza/v1/markers', [
                    'search' => $customerEmail
                ]);

            if ($response->successful()) {
                $markers = $response->json();
                if (!empty($markers)) {
                    return [
                        'success' => true,
                        'location_data' => $markers[0] // Return first match
                    ];
                }
            }

            return [
                'success' => false,
                'message' => 'No location data found for customer'
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get customer location from WP Go Maps: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Enhanced route optimization using WP Go Maps Pro features
     */
    public function optimizeRouteWithWPGoMaps($deliveries)
    {
        try {
            $optimizedDeliveries = [];
            $enhancedDeliveries = [];

            // Enhance deliveries with location data from WP Go Maps
            foreach ($deliveries as $delivery) {
                $locationData = $this->getCustomerLocationData($delivery['email'] ?? '');
                
                if ($locationData['success']) {
                    $delivery['wp_maps_data'] = $locationData['location_data'];
                    $delivery['coordinates'] = [
                        'lat' => $locationData['location_data']['lat'] ?? null,
                        'lng' => $locationData['location_data']['lng'] ?? null
                    ];
                }
                
                $enhancedDeliveries[] = $delivery;
            }

            // Use WP Go Maps Pro's built-in route optimization if available
            $routeOptimization = $this->callWPGoMapsRouteOptimization($enhancedDeliveries);
            
            if ($routeOptimization['success']) {
                return $routeOptimization;
            }

            // Fallback to standard Google Maps optimization
            return [
                'success' => true,
                'deliveries' => $enhancedDeliveries,
                'message' => 'Enhanced with WP Go Maps data, using standard optimization'
            ];

        } catch (\Exception $e) {
            Log::error('WP Go Maps route optimization failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Call WP Go Maps Pro route optimization API
     */
    private function callWPGoMapsRouteOptimization($deliveries)
    {
        try {
            // This would use WP Go Maps Pro's route optimization features
            // The exact API depends on your WP Go Maps Pro version and setup
            
            $routeData = [
                'waypoints' => [],
                'optimization' => true,
                'travelMode' => 'DRIVING'
            ];

            foreach ($deliveries as $delivery) {
                if (isset($delivery['coordinates']) && $delivery['coordinates']['lat']) {
                    $routeData['waypoints'][] = [
                        'location' => [
                            'lat' => $delivery['coordinates']['lat'],
                            'lng' => $delivery['coordinates']['lng']
                        ],
                        'stopover' => true
                    ];
                }
            }

            // Call WP Go Maps Pro route optimization endpoint
            $response = Http::timeout(60)
                ->withHeaders([
                    'X-WC-API-Key' => $this->apiKey,
                    'Content-Type' => 'application/json'
                ])
                ->post($this->baseUrl . '/wp-json/wpgmza/v1/routes/optimize', $routeData);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'optimized_route' => $response->json(),
                    'source' => 'wp_go_maps_pro'
                ];
            }

            return [
                'success' => false,
                'error' => 'WP Go Maps Pro optimization not available'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Format delivery address for WP Go Maps
     */
    private function formatDeliveryAddress($delivery)
    {
        if (isset($delivery['address']) && is_array($delivery['address'])) {
            return implode(', ', array_filter($delivery['address']));
        } elseif (isset($delivery['address']) && is_string($delivery['address'])) {
            return $delivery['address'];
        }
        
        return '';
    }

    /**
     * Generate marker description for delivery
     */
    private function generateMarkerDescription($delivery, $stopNumber)
    {
        $description = "<strong>Stop #{$stopNumber}</strong><br>";
        $description .= "<strong>Customer:</strong> " . ($delivery['name'] ?? 'Unknown') . "<br>";
        
        if (isset($delivery['phone']) && $delivery['phone']) {
            $description .= "<strong>Phone:</strong> " . $delivery['phone'] . "<br>";
        }
        
        if (isset($delivery['products']) && !empty($delivery['products'])) {
            $description .= "<strong>Products:</strong><br>";
            foreach ($delivery['products'] as $product) {
                $description .= "• " . ($product['name'] ?? 'Product') . "<br>";
            }
        }
        
        return $description;
    }

    /**
     * Create shareable map link for drivers
     */
    public function createShareableMapLink($mapId)
    {
        return $this->baseUrl . '/delivery-map/?map_id=' . $mapId;
    }

    /**
     * Export route data for WP Go Maps integration
     */
    public function exportRouteForWordPress($deliveries, $routeDetails, $deliveryDate)
    {
        $exportData = [
            'date' => $deliveryDate,
            'total_deliveries' => count($deliveries),
            'route_details' => $routeDetails,
            'deliveries' => $deliveries,
            'created_at' => now()->toISOString(),
            'status' => 'active'
        ];

        try {
            // Save route data to WordPress custom post type or meta
            $response = Http::timeout(30)
                ->withHeaders([
                    'X-WC-API-Key' => $this->apiKey,
                    'Content-Type' => 'application/json'
                ])
                ->post($this->baseUrl . '/wp-json/wp/v2/delivery_routes', [
                    'title' => 'Delivery Route - ' . $deliveryDate,
                    'status' => 'publish',
                    'meta' => [
                        'route_data' => json_encode($exportData)
                    ]
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'wordpress_post_id' => $response->json()['id'],
                    'message' => 'Route exported to WordPress successfully'
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to export route to WordPress'
            ];

        } catch (\Exception $e) {
            Log::error('Failed to export route to WordPress: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
