<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RouteOptimizationService
{
    private $googleMapsApiKey;
    private $baseUrl = 'https://maps.googleapis.com/maps/api';
    private $wpGoMapsService;

    public function __construct(WPGoMapsService $wpGoMapsService = null)
    {
        $this->googleMapsApiKey = config('services.google_maps.api_key');
        $this->wpGoMapsService = $wpGoMapsService ?? new WPGoMapsService();
    }

    /**
     * Optimize delivery route using Google Maps API with WP Go Maps Pro enhancement
     */
    public function optimizeRoute($deliveries, $startLocation = null)
    {
        if (empty($deliveries)) {
            return ['error' => 'No deliveries provided'];
        }

        try {
            // Default start location (your farm/depot)
            $start = $startLocation ?? config('services.delivery.depot_address', 'Middleworld Farms, UK');
            
            // Try to enhance with WP Go Maps Pro data first
            $wpMapsResult = $this->wpGoMapsService->optimizeRouteWithWPGoMaps($deliveries);
            if ($wpMapsResult['success'] && isset($wpMapsResult['optimized_route'])) {
                Log::info('Using WP Go Maps Pro optimization');
                $deliveries = $wpMapsResult['deliveries'] ?? $deliveries;
            }
            
            // Extract addresses from deliveries
            $destinations = $this->extractAddresses($deliveries);
            
            if (empty($destinations)) {
                return ['error' => 'No valid addresses found'];
            }

            // Get optimized route from Google Maps
            $optimizedOrder = $this->getOptimizedWaypoints($start, $destinations);
            
            // Reorder deliveries based on optimization
            $optimizedDeliveries = $this->reorderDeliveries($deliveries, $optimizedOrder);
            
            // Get route details (distances, times, directions)
            $routeDetails = $this->getRouteDetails($start, $optimizedDeliveries);
            
            // Create shareable map in WP Go Maps Pro
            $wpMapResult = $this->wpGoMapsService->createDeliveryRouteMap(
                $optimizedDeliveries, 
                $routeDetails, 
                date('Y-m-d')
            );
            
            $result = [
                'success' => true,
                'optimized_deliveries' => $optimizedDeliveries,
                'route_details' => $routeDetails,
                'total_distance' => $routeDetails['total_distance'] ?? 0,
                'total_duration' => $routeDetails['total_duration'] ?? 0,
                'start_location' => $start,
                'optimization_source' => $wpMapsResult['success'] ? 'wp_go_maps_enhanced' : 'google_maps'
            ];

            // Add WP Go Maps data if available
            if ($wpMapResult['success']) {
                $result['wp_maps'] = [
                    'map_id' => $wpMapResult['map_id'],
                    'shortcode' => $wpMapResult['shortcode'],
                    'shareable_link' => $this->wpGoMapsService->createShareableMapLink($wpMapResult['map_id'])
                ];
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('Route optimization failed: ' . $e->getMessage());
            return ['error' => 'Route optimization failed: ' . $e->getMessage()];
        }
    }

    /**
     * Extract and geocode addresses from deliveries
     */
    private function extractAddresses($deliveries)
    {
        $addresses = [];
        
        foreach ($deliveries as $index => $delivery) {
            $address = $this->formatAddress($delivery);
            if ($address) {
                $addresses[$index] = [
                    'address' => $address,
                    'customer_name' => $delivery['name'] ?? 'Unknown',
                    'customer_email' => $delivery['email'] ?? '',
                    'delivery_id' => $delivery['id'] ?? $index
                ];
            }
        }
        
        return $addresses;
    }

    /**
     * Format delivery address for Google Maps
     */
    private function formatAddress($delivery)
    {
        if (isset($delivery['address']) && is_array($delivery['address'])) {
            return implode(', ', array_filter($delivery['address']));
        } elseif (isset($delivery['address']) && is_string($delivery['address'])) {
            return $delivery['address'];
        }
        
        // Try to build address from individual fields
        $addressParts = [];
        if (isset($delivery['address_1'])) $addressParts[] = $delivery['address_1'];
        if (isset($delivery['address_2'])) $addressParts[] = $delivery['address_2'];
        if (isset($delivery['city'])) $addressParts[] = $delivery['city'];
        if (isset($delivery['postcode'])) $addressParts[] = $delivery['postcode'];
        
        return !empty($addressParts) ? implode(', ', $addressParts) : null;
    }

    /**
     * Get optimized waypoints using Google Maps Directions API
     */
    private function getOptimizedWaypoints($start, $destinations)
    {
        if (count($destinations) <= 1) {
            return array_keys($destinations);
        }

        $waypoints = [];
        foreach ($destinations as $destination) {
            $waypoints[] = $destination['address'];
        }

        $response = Http::get($this->baseUrl . '/directions/json', [
            'origin' => $start,
            'destination' => $start, // Return to depot
            'waypoints' => 'optimize:true|' . implode('|', $waypoints),
            'key' => $this->googleMapsApiKey
        ]);

        if ($response->successful()) {
            $data = $response->json();
            
            if ($data['status'] === 'OK' && isset($data['routes'][0]['waypoint_order'])) {
                return $data['routes'][0]['waypoint_order'];
            }
        }

        // Fallback: return original order
        return array_keys($destinations);
    }

    /**
     * Reorder deliveries based on optimized waypoint order
     */
    private function reorderDeliveries($deliveries, $optimizedOrder)
    {
        $reordered = [];
        
        foreach ($optimizedOrder as $originalIndex) {
            if (isset($deliveries[$originalIndex])) {
                $reordered[] = $deliveries[$originalIndex];
            }
        }
        
        return $reordered;
    }

    /**
     * Get detailed route information
     */
    private function getRouteDetails($start, $optimizedDeliveries)
    {
        $waypoints = [];
        foreach ($optimizedDeliveries as $delivery) {
            $address = $this->formatAddress($delivery);
            if ($address) {
                $waypoints[] = $address;
            }
        }

        if (empty($waypoints)) {
            return ['total_distance' => 0, 'total_duration' => 0];
        }

        $response = Http::get($this->baseUrl . '/directions/json', [
            'origin' => $start,
            'destination' => $start,
            'waypoints' => implode('|', $waypoints),
            'key' => $this->googleMapsApiKey
        ]);

        if ($response->successful()) {
            $data = $response->json();
            
            if ($data['status'] === 'OK' && isset($data['routes'][0])) {
                $route = $data['routes'][0];
                
                // Calculate total distance and duration across all legs
                $totalDistance = 0;
                $totalDuration = 0;
                
                foreach ($route['legs'] as $leg) {
                    $totalDistance += $leg['distance']['value'] ?? 0; // in meters
                    $totalDuration += $leg['duration']['value'] ?? 0; // in seconds
                }
                
                // Convert to readable format
                $totalDistanceKm = $totalDistance / 1000;
                $totalDurationMinutes = $totalDuration / 60;
                
                return [
                    'total_distance' => $totalDistanceKm,
                    'total_duration' => $totalDurationMinutes,
                    'total_distance_text' => number_format($totalDistanceKm, 1) . ' km',
                    'total_duration_text' => number_format($totalDurationMinutes, 0) . ' mins',
                    'steps' => $this->extractSteps($route['legs']),
                    'polyline' => $route['overview_polyline']['points'] ?? '',
                    'bounds' => $route['bounds'] ?? null
                ];
            }
        }

        return ['total_distance' => 0, 'total_duration' => 0];
    }

    /**
     * Extract step-by-step directions
     */
    private function extractSteps($legs)
    {
        $steps = [];
        
        foreach ($legs as $legIndex => $leg) {
            $steps[] = [
                'leg' => $legIndex + 1,
                'distance' => $leg['distance']['text'] ?? '0 km',
                'duration' => $leg['duration']['text'] ?? '0 mins',
                'start_address' => $leg['start_address'] ?? '',
                'end_address' => $leg['end_address'] ?? ''
            ];
        }
        
        return $steps;
    }

    /**
     * Geocode an address to get coordinates
     */
    public function geocodeAddress($address)
    {
        $response = Http::get($this->baseUrl . '/geocode/json', [
            'address' => $address,
            'key' => $this->googleMapsApiKey
        ]);

        if ($response->successful()) {
            $data = $response->json();
            
            if ($data['status'] === 'OK' && !empty($data['results'])) {
                $location = $data['results'][0]['geometry']['location'];
                return [
                    'lat' => $location['lat'],
                    'lng' => $location['lng'],
                    'formatted_address' => $data['results'][0]['formatted_address']
                ];
            }
        }

        return null;
    }

    /**
     * Calculate distance matrix between multiple points
     */
    public function getDistanceMatrix($origins, $destinations)
    {
        $response = Http::get($this->baseUrl . '/distancematrix/json', [
            'origins' => implode('|', $origins),
            'destinations' => implode('|', $destinations),
            'units' => 'metric',
            'key' => $this->googleMapsApiKey
        ]);

        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }
}
