<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\WpApiService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class DashboardController extends Controller
{
    protected $wpApiService;

    public function __construct(WpApiService $wpApiService)
    {
        $this->wpApiService = $wpApiService;
    }

    public function index()
    {
        try {
            // Get delivery statistics
            $deliveryStats = $this->getDeliveryStats();
            
            // Get customer statistics
            $customerStats = $this->getCustomerStats();
            
            // Get fortnightly information
            $fortnightlyInfo = $this->getFortnightlyInfo();
            
            // Trigger async prefetching of orders in background
            $this->prefetchOrdersInBackground();
            
            return view('admin.dashboard', compact('deliveryStats', 'customerStats', 'fortnightlyInfo'));
            
        } catch (\Exception $e) {
            // Fallback stats if database connection fails
            $deliveryStats = [
                'active' => 0,
                'collections' => 0,
                'total' => 0
            ];
            
            $customerStats = [
                'total' => 0,
                'active' => 0
            ];
            
            $fortnightlyInfo = [
                'current_week' => 'A',
                'weekly_count' => 0,
                'fortnightly_count' => 0,
                'active_this_week' => 0
            ];
            
            return view('admin.dashboard', compact('deliveryStats', 'customerStats', 'fortnightlyInfo'));
        }
    }

    private function getDeliveryStats()
    {
        try {
            // Use the same approach as DeliveryController
            $wpApi = $this->wpApiService;
            
            // Get raw data the same way as DeliveryController
            $rawData = [];
            try {
                $rawData = $wpApi->getDeliveryScheduleData(500);
            } catch (\Exception $e) {
                \Log::error('Dashboard delivery schedule API timeout: ' . $e->getMessage());
                return [
                    'active' => 0,
                    'collections' => 0,
                    'total' => 0,
                    'processing' => 0,
                    'completed' => 0,
                    'on_hold' => 0
                ];
            }
            
            if (empty($rawData)) {
                \Log::info('Dashboard: No raw delivery schedule data');
                return [
                    'active' => 0,
                    'collections' => 0,
                    'total' => 0,
                    'processing' => 0,
                    'completed' => 0,
                    'on_hold' => 0
                ];
            }
            
            // Use current week like DeliveryController
            $currentWeek = (int) date('W');
            
            // Transform data the same way as DeliveryController
            $scheduleData = $this->transformScheduleData($rawData, $currentWeek);
            
            // Add completion data the same way as DeliveryController
            $scheduleData = $this->addCompletionData($scheduleData);
            
            // Calculate ACTIVE totals only (same as delivery schedule view)
            $activeDeliveries = 0;
            $activeCollections = 0;
            
            foreach ($scheduleData['data'] as $dateData) {
                // Count only active deliveries
                if(isset($dateData['deliveries'])) {
                    foreach($dateData['deliveries'] as $delivery) {
                        if(isset($delivery['status']) && $delivery['status'] === 'active') {
                            $activeDeliveries++;
                        }
                    }
                }
                
                // Count only active collections
                if(isset($dateData['collections'])) {
                    foreach($dateData['collections'] as $collection) {
                        if(isset($collection['status']) && $collection['status'] === 'active') {
                            $activeCollections++;
                        }
                    }
                }
            }
            
            \Log::info('Dashboard Active Counts', [
                'active_deliveries' => $activeDeliveries,
                'active_collections' => $activeCollections,
                'schedule_dates' => count($scheduleData['data'])
            ]);
            
            return [
                'active' => $activeDeliveries,
                'collections' => $activeCollections,
                'total' => $activeDeliveries + $activeCollections,
                'processing' => $activeDeliveries,
                'completed' => 0,
                'on_hold' => 0
            ];

        } catch (\Exception $e) {
            \Log::error('Dashboard delivery stats error: ' . $e->getMessage());
            return [
                'active' => 0,
                'collections' => 0,
                'total' => 0,
                'processing' => 0,
                'completed' => 0,
                'on_hold' => 0
            ];
        }
    }

    private function determineCustomerType($shippingTotal, $subscription = null)
    {
        // PRIORITY 1: Check shipping method for explicit collection indicators
        // This takes precedence over everything else including addresses
        if ($subscription && isset($subscription['shipping_lines']) && is_array($subscription['shipping_lines'])) {
            foreach ($subscription['shipping_lines'] as $shippingLine) {
                if (isset($shippingLine['method_title']) && is_string($shippingLine['method_title'])) {
                    $methodTitle = strtolower($shippingLine['method_title']);
                    if (strpos($methodTitle, 'collection') !== false || strpos($methodTitle, 'pickup') !== false) {
                        return 'collections';
                    }
                }
            }
        }
        
        // PRIORITY 2: Check shipping classes in line_items for collection indicators
        if ($subscription && isset($subscription['line_items']) && is_array($subscription['line_items'])) {
            foreach ($subscription['line_items'] as $item) {
                if (isset($item['shipping_class']) && is_string($item['shipping_class'])) {
                    $shippingClass = strtolower($item['shipping_class']);
                    if (strpos($shippingClass, 'collection') !== false || strpos($shippingClass, 'pickup') !== false) {
                        return 'collections';
                    }
                }
            }
        }
        
        // PRIORITY 3: Check meta_data for shipping class collection indicators
        if ($subscription && isset($subscription['meta_data']) && is_array($subscription['meta_data'])) {
            foreach ($subscription['meta_data'] as $meta) {
                if (isset($meta['key']) && isset($meta['value']) && is_string($meta['key']) && is_string($meta['value'])) {
                    $key = strtolower($meta['key']);
                    $value = strtolower($meta['value']);
                    
                    if (strpos($key, 'shipping') !== false && (strpos($value, 'collection') !== false || strpos($value, 'pickup') !== false)) {
                        return 'collections';
                    }
                }
            }
        }
        
        // PRIORITY 4: Check shipping total - if greater than 0, it's delivery
        $normalizedShippingTotal = $this->normalizeShippingTotal($shippingTotal);
        if ($normalizedShippingTotal > 0) {
            return 'deliveries';
        }
        
        // PRIORITY 5: Check if customer has a delivery address (only matters if no collection method found above)
        $hasDeliveryAddress = false;
        if ($subscription) {
            // Check shipping address first
            if (isset($subscription['shipping']['address_1']) && !empty(trim($subscription['shipping']['address_1']))) {
                $hasDeliveryAddress = true;
            }
            // Fallback to billing address
            elseif (isset($subscription['billing']['address_1']) && !empty(trim($subscription['billing']['address_1']))) {
                $hasDeliveryAddress = true;
            }
        }
        
        // If customer has a delivery address but no shipping cost and no collection method, 
        // it might be a delivery with free shipping or promotional delivery
        if ($hasDeliveryAddress) {
            return 'deliveries';
        }
        
        // Default to collection if no shipping cost, no delivery address, and no other indicators
        return 'collections';
    }

    // Simplified version of DeliveryController's transformScheduleData for counting
    private function transformScheduleData($rawData, $selectedWeek = null)
    {
        // Use current week if no selectedWeek provided
        if ($selectedWeek === null) {
            $selectedWeek = (int) date('W');
        }
        $selectedWeek = (int) $selectedWeek;
        
        // Calculate the selected week type (A or B)
        $selectedWeekType = ($selectedWeek % 2 === 1) ? 'A' : 'B';
        
        $result = ['data' => []];
        
        // If rawData is a flat API response (list of subscriptions), split into deliveries/collections
        if (isset($rawData[0]) && is_array($rawData[0])) {
            $subscriptions = $rawData;
            
            foreach ($subscriptions as $sub) {
                // Use the same delivery type determination as DeliveryController
                $type = $this->determineCustomerType($sub['shipping_total'] ?? null, $sub);
                
                // Extract frequency - same logic as DeliveryController
                $frequency = 'Weekly'; // Default
                
                // Method 1: Check WooCommerce subscription billing_period and billing_interval (standard approach)
                if (isset($sub['billing_period']) && strtolower($sub['billing_period']) === 'week') {
                    $interval = intval($sub['billing_interval'] ?? 1);
                    if ($interval === 2) {
                        $frequency = 'Fortnightly';
                    } elseif ($interval === 1) {
                        $frequency = 'Weekly';
                    }
                }
                
                // Method 2: Check line items meta_data as fallback
                if ($frequency === 'Weekly' && isset($sub['line_items'][0]['meta_data'])) {
                    foreach ($sub['line_items'][0]['meta_data'] as $meta) {
                        if ($meta['key'] === 'frequency') {
                            $frequency = $meta['value'];
                            break;
                        }
                    }
                }
                
                // Method 3: Check top-level meta_data as final fallback
                if ($frequency === 'Weekly' && isset($sub['meta_data'])) {
                    foreach ($sub['meta_data'] as $meta) {
                        if ($meta['key'] === 'frequency' || $meta['key'] === '_subscription_frequency') {
                            $frequency = $meta['value'];
                            break;
                        }
                    }
                }
                
                // Method 4: Check product name for frequency indicators
                if ($frequency === 'Weekly' && isset($sub['line_items'][0]['name'])) {
                    $productName = strtolower($sub['line_items'][0]['name']);
                    if (strpos($productName, 'fortnightly') !== false) {
                        $frequency = 'Fortnightly';
                    } elseif (strpos($productName, 'weekly') !== false) {
                        $frequency = 'Weekly';
                    }
                }
                
                // Normalize frequency values
                $frequency = trim(strtolower($frequency));
                if (strpos($frequency, 'fortnightly') !== false) {
                    $frequency = 'Fortnightly';
                } elseif (strpos($frequency, 'weekly') !== false) {
                    $frequency = 'Weekly';
                } else {
                    $frequency = 'Weekly';
                }
                
                // Extract customer week type from meta_data if available
                $customerWeekType = 'Weekly'; // Default
                
                // Check meta_data from API for customer week type
                if (isset($sub['meta_data'])) {
                    foreach ($sub['meta_data'] as $meta) {
                        if ($meta['key'] === 'customer_week_type') {
                            $customerWeekType = $meta['value'];
                            break;
                        }
                    }
                }
                
                // Week filtering logic - same as DeliveryController
                $shouldIncludeInSelectedWeek = true;
                
                if (strtolower($frequency) === 'fortnightly') {
                    // Use customer week type from meta_data if available, otherwise auto-assign
                    if ($customerWeekType === 'Weekly' || !in_array($customerWeekType, ['A', 'B'])) {
                        // Auto-assign fortnightly customers to a week type based on their subscription ID
                        $customerWeekType = ((int)$sub['id'] % 2 === 1) ? 'A' : 'B';
                    }
                    
                    // Check if this fortnightly customer should appear in the selected week
                    $shouldIncludeInSelectedWeek = ($customerWeekType === $selectedWeekType);
                    
                    // Debug logging for fortnightlies
                    \Log::info('Dashboard Fortnightly Filtering', [
                        'subscription_id' => $sub['id'],
                        'frequency' => $frequency,
                        'customer_week_type' => $customerWeekType,
                        'selected_week_type' => $selectedWeekType,
                        'should_include' => $shouldIncludeInSelectedWeek,
                        'type' => $type
                    ]);
                }
                
                // Skip this subscription if it shouldn't appear in the selected week
                if (!$shouldIncludeInSelectedWeek) {
                    continue;
                }
                
                // Add to appropriate type array
                $dateKey = date('Y-m-d'); // Use today's date as key
                if (!isset($result['data'][$dateKey])) {
                    $result['data'][$dateKey] = ['deliveries' => [], 'collections' => []];
                }
                
                if ($type === 'deliveries') {
                    $result['data'][$dateKey]['deliveries'][] = $sub;
                } else {
                    $result['data'][$dateKey]['collections'][] = $sub;
                }
            }
        }
        
        return $result;
    }

    // Simplified version of DeliveryController's addCompletionData
    private function addCompletionData($scheduleData)
    {
        // For dashboard counting purposes, we don't need actual completion data
        // Just return the schedule data as-is
        return $scheduleData;
    }

    private function normalizeShippingTotal($shippingTotal)
    {
        if ($shippingTotal === null || $shippingTotal === '') {
            return 0.0;
        }
        
        if (is_string($shippingTotal)) {
            $shippingTotal = trim($shippingTotal);
            if (!is_numeric($shippingTotal)) {
                return 0.0;
            }
        }
        
        return (float) $shippingTotal;
    }

    private function simpleTransformForWeek($rawData, $selectedWeek)
    {
        $result = [];
        
        if (!is_array($rawData)) {
            return $result;
        }
        
        // If rawData is a flat array of subscriptions (from API)
        if (isset($rawData[0]['id'])) {
            foreach ($rawData as $subscription) {
                // Get the delivery/collection type
                $type = 'deliveries'; // Default
                if (isset($subscription['shipping_total']) && $subscription['shipping_total'] == 0) {
                    $type = 'collections';
                }
                
                // Get delivery dates for this subscription (simplified)
                $dates = $this->getSubscriptionDates($subscription, $selectedWeek);
                
                foreach ($dates as $date) {
                    if (!isset($result[$date])) {
                        $result[$date] = ['deliveries' => [], 'collections' => []];
                    }
                    
                    // Add subscription to appropriate type
                    if ($type === 'collections') {
                        $result[$date]['collections'][] = $subscription;
                    } else {
                        $result[$date]['deliveries'][] = $subscription;
                    }
                }
            }
        }
        
        return $result;
    }
    
    private function getSubscriptionDates($subscription, $selectedWeek)
    {
        $dates = [];
        $currentWeek = (int) date('W');
        
        // Simplified date calculation - just return current week's Monday
        if ($selectedWeek == $currentWeek) {
            $dates[] = date('Y-m-d', strtotime('monday this week'));
        }
        
        return $dates;
    }

    private function getCustomerStats()
    {
        try {
            $wpApiService = new WpApiService();
            
            // Get customer count from WooCommerce API
            $customerData = $wpApiService->getCustomerCount();
            
            // Log the response for debugging
            Log::info('Customer API Response:', [
                'data' => $customerData,
                'type' => gettype($customerData)
            ]);
            
            // Handle different response formats
            if (is_array($customerData) && isset($customerData['count'])) {
                $customerCount = intval($customerData['count']);
            } elseif (is_numeric($customerData)) {
                $customerCount = intval($customerData);
            } elseif (is_array($customerData) && isset($customerData['total'])) {
                $customerCount = intval($customerData['total']);
            } else {
                // Fallback: try to count array elements if it's an array of customers
                $customerCount = is_array($customerData) ? count($customerData) : 0;
            }
            
            Log::info('Final customer count:', ['count' => $customerCount]);
            
            return [
                'total' => $customerCount,
                'active' => $customerCount,
                'inactive' => 0
            ];
            
        } catch (\Exception $e) {
            Log::error('Error fetching customer stats: ' . $e->getMessage());
            
            // Return zero values on error to prevent dashboard crash
            return [
                'total' => 0,
                'active' => 0,
                'inactive' => 0
            ];
        }
    }

    private function getFortnightlyInfo()
    {
        try {
            // Get current week information
            $currentWeek = (int) date('W');
            $currentWeekType = ($currentWeek % 2 === 1) ? 'A' : 'B';
            
            // Get subscription data to estimate fortnightly info
            $scheduleData = $this->wpApiService->getDeliveryScheduleData();
            $totalSubscriptions = count($scheduleData['subscriptions'] ?? []);
            
            // Estimate weekly vs fortnightly split (this is a simplified calculation)
            $weeklyCount = intval($totalSubscriptions * 0.6); // Estimate 60% are weekly
            $fortnightlyCount = $totalSubscriptions - $weeklyCount;
            
            return [
                'current_week' => $currentWeekType,
                'current_iso_week' => $currentWeek,
                'weekly_count' => $weeklyCount,
                'fortnightly_count' => $fortnightlyCount,
                'active_this_week' => $fortnightlyCount, // Simplified estimate
                'next_week_type' => ($currentWeekType === 'A') ? 'B' : 'A',
                'fortnightly_subscriptions' => collect($scheduleData['subscriptions'] ?? [])
            ];
            
        } catch (\Exception $e) {
            // Fallback data if fortnightly detection fails
            $currentWeek = (int) date('W');
            $currentWeekType = ($currentWeek % 2 === 1) ? 'A' : 'B';
            
            return [
                'current_week' => $currentWeekType,
                'current_iso_week' => $currentWeek,
                'weekly_count' => 0,
                'fortnightly_count' => 0,
                'active_this_week' => 0,
                'next_week_type' => ($currentWeekType === 'A') ? 'B' : 'A',
                'fortnightly_subscriptions' => collect(),
                'error' => $e->getMessage()
            ];
        }
    }

    public function getSystemHealth()
    {
        try {
            // Check API connection
            $apiStatus = $this->wpApiService->testConnection();
            
            // Check Laravel components
            $laravel = [
                'version' => app()->version(),
                'environment' => app()->environment(),
                'debug' => config('app.debug'),
                'timezone' => config('app.timezone')
            ];
            
            // Check disk space (basic)
            $diskSpace = disk_free_space('/') / (1024 * 1024 * 1024); // GB
            
            return [
                'api' => $apiStatus,
                'laravel' => $laravel,
                'disk_space_gb' => round($diskSpace, 2),
                'php_version' => PHP_VERSION,
                'memory_usage' => round(memory_get_usage(true) / (1024 * 1024), 2) . ' MB'
            ];
            
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
                'status' => 'error'
            ];
        }
    }

    /**
     * Get FarmOS map data for the dashboard with enhanced diagnostics
     */
    public function farmosMapData()
    {
        $started = microtime(true);
        $envClient = (bool) env('FARMOS_OAUTH_CLIENT_ID');
        $envSecret = (bool) env('FARMOS_OAUTH_CLIENT_SECRET');
        try {
            $farmosService = app(\App\Services\FarmOSApi::class);
            $geometryData = $farmosService->getGeometryAssets();
            $featureCount = is_array($geometryData) && isset($geometryData['features']) ? count($geometryData['features']) : 0;
            Log::info('FarmOS map data success', [
                'features' => $featureCount,
                'oauth_client_present' => $envClient,
                'oauth_secret_present' => $envSecret,
                'duration_ms' => round((microtime(true) - $started) * 1000, 1)
            ]);
            if ($featureCount === 0) {
                Log::warning('FarmOS map returned zero features');
            }
            return response()->json($geometryData ?: [
                'type' => 'FeatureCollection',
                'features' => []
            ]);
        } catch (\Throwable $e) {
            Log::error('FarmOS map data error', [
                'message' => $e->getMessage(),
                'oauth_client_present' => $envClient,
                'oauth_secret_present' => $envSecret,
                'trace_top' => collect(explode("\n", $e->getTraceAsString()))->take(5)->all(),
                'duration_ms' => round((microtime(true) - $started) * 1000, 1)
            ]);
            return response()->json([
                'error' => 'Unable to retrieve FarmOS map data',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function plantingRecommendations()
    {
        return view('admin.planting-recommendations');
    }

    /**
     * Prefetch orders in background when dashboard loads
     */
    private function prefetchOrdersInBackground()
    {
        try {
            // Simply prefetch the first few pages synchronously
            // Since we have caching, this will be fast on subsequent loads
            $orderController = app(\App\Http\Controllers\Admin\OrderController::class);
            $orderController->prefetchOrders();
            
        } catch (\Exception $e) {
            // Don't fail dashboard if prefetching fails
            Log::warning('Failed to prefetch orders', ['error' => $e->getMessage()]);
        }
    }
}
