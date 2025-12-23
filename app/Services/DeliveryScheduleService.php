<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DeliveryScheduleService
{
    private $baseUrl;
    private $apiKey;
    private $wpApiService;

    public function __construct(WpApiService $wpApiService)
    {
        $this->baseUrl = config('services.woocommerce.api_url', 'https://middleworldfarms.org');
        $this->apiKey = config('services.wordpress.api_key');
        $this->wpApiService = $wpApiService;
    }

    public function getSchedule($startDate = null, $endDate = null, $status = 'active')
    {
        try {
            Log::info('Getting delivery schedule data via WP API service');
            
            // Use WP API service instead of direct database connection
            $scheduleData = $this->wpApiService->getDeliveryScheduleData(100);
            
            if (isset($scheduleData['error'])) {
                throw new \Exception($scheduleData['error']);
            }

            return [
                'success' => true,
                'deliveries' => isset($scheduleData['deliveries']) && $scheduleData['deliveries'] ? $scheduleData['deliveries']->toArray() : [],
                'collections' => isset($scheduleData['collections']) && $scheduleData['collections'] ? $scheduleData['collections']->toArray() : [],
                'total_deliveries' => $scheduleData['total_deliveries'] ?? 0,
                'total_collections' => $scheduleData['total_collections'] ?? 0,
                'data_source' => 'wp_api_service',
                'message' => 'Data retrieved via WordPress API service'
            ];
            
        } catch (\Exception $e) {
            Log::warning('Direct database failed, falling back to API', [
                'error' => $e->getMessage()
            ]);
            
            // Fallback to API method if direct database fails
            return $this->getScheduleViaAPI($startDate, $endDate, $status);
        }
    }

    /**
     * Fallback method using WooCommerce API
     */
    private function getScheduleViaAPI($startDate = null, $endDate = null, $status = 'active')
    {
        try {
            $consumerKey = config('services.woocommerce.consumer_key');
            $consumerSecret = config('services.woocommerce.consumer_secret');
            
            $params = [
                'status' => $status,
                'per_page' => 100,
                'orderby' => 'date',
                'order' => 'desc'
            ];

            if ($response->successful()) {
                $subscriptions = $response->json();
                
                if (!empty($subscriptions)) {
                    // Transform WooCommerce subscription data into delivery schedule format
                    return $this->transformSubscriptionsToSchedule($subscriptions);
                }
            }

            // If no subscriptions found, try regular orders
            $orderResponse = Http::timeout(30)
                ->withBasicAuth($consumerKey, $consumerSecret)
                ->get($this->baseUrl . '/wp-json/wc/v3/orders', array_merge($params, ['per_page' => 50]));

            if ($orderResponse->successful()) {
                $orders = $orderResponse->json();
                
                if (!empty($orders)) {
                    return $this->transformOrdersToSchedule($orders);
                }
            }

            // If no WooCommerce data available, return structure indicating no data
            return [
                'success' => true,
                'data' => [],
                'message' => 'No delivery schedule data available from WooCommerce'
            ];
            
        } catch (\Exception $e) {
            Log::error('Exception in DeliveryScheduleService::getSchedule', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'data' => [],
                'error' => $e->getMessage()
            ];
        }
    }

    public function fetchSchedule()
    {
        return $this->getSchedule();
    }

    public function testConnection()
    {
        try {
            // Test the actual working WooCommerce API endpoint that provides the data
            $consumerKey = config('services.woocommerce.consumer_key');
            $consumerSecret = config('services.woocommerce.consumer_secret');
            
            $response = Http::timeout(10)
                ->withBasicAuth($consumerKey, $consumerSecret)
                ->get($this->baseUrl . '/wp-json/wc/v3/subscriptions', [
                    'per_page' => 1,
                    'status' => 'active'
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'WooCommerce API connected'
                ];
            }

            return [
                'success' => false,
                'status' => $response->status(),
                'body' => $response->body()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function testAuth()
    {
        try {
            // First check if MWF API key is configured
            if (empty($this->apiKey)) {
                return [
                    'success' => false,
                    'message' => 'MWF API key is not configured'
                ];
            }

            // Try to test the MWF API endpoint first
            $response = Http::timeout(30)
                ->withHeaders([
                    'X-WC-API-Key' => $this->apiKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ])
                ->get($this->baseUrl . '/wp-json/mwf/v1/users/recent', [
                    'per_page' => 1
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'MWF API authentication successful'
                ];
            }

            // If the custom endpoint fails, check if it's a 404 (endpoint doesn't exist)
            if ($response->status() === 404) {
                // Try to verify the API key against a generic MWF endpoint
                $testResponse = Http::timeout(10)
                    ->withHeaders([
                        'X-WC-API-Key' => $this->apiKey,
                        'Accept' => 'application/json'
                    ])
                    ->get($this->baseUrl . '/wp-json/mwf/v1/users/search', ['search' => 'test']);

                if ($testResponse->successful()) {
                    return [
                        'success' => false,
                        'message' => 'MWF API key is valid, but delivery schedule data not available. Check WooCommerce subscriptions configuration.'
                    ];
                }
            }

            // Get detailed error information
            $errorMessage = 'Authentication failed';
            $responseBody = $response->body();
            
            // Try to parse the response for a better error message
            if ($responseBody) {
                $decoded = json_decode($responseBody, true);
                if (isset($decoded['message'])) {
                    $errorMessage = $decoded['message'];
                } elseif (isset($decoded['error'])) {
                    $errorMessage = $decoded['error'];
                }
            }

            return [
                'success' => false,
                'message' => $errorMessage,
                'status' => $response->status(),
                'details' => $responseBody
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Calculate if the current week is Week A or Week B for a specific customer
     * This ensures fortnightly customers are split between Week A and Week B
     * Based on Thursday-to-Wednesday delivery weeks (Thursday = start of delivery week)
     * 
     * @param string $subscriptionId Subscription ID to determine A/B assignment
     * @param string $targetDate Date to check (defaults to current date)
     * @return string 'A' or 'B'
     */
    public function calculateWeekAB($subscriptionId, $targetDate = null)
    {
        if (!$targetDate) {
            $targetDate = date('Y-m-d');
        }

        // Get the Thursday of the current delivery week
        // If today is Thu/Fri/Sat/Sun/Mon/Tue/Wed, find the most recent Thursday
        $targetTimestamp = strtotime($targetDate);
        $dayOfWeek = date('N', $targetTimestamp); // 1=Monday, 4=Thursday, 7=Sunday
        
        // Calculate days to subtract to get to Thursday
        if ($dayOfWeek >= 4) {
            // If Thu/Fri/Sat/Sun, go back to this week's Thursday
            $daysBack = $dayOfWeek - 4;
        } else {
            // If Mon/Tue/Wed, go back to last week's Thursday
            $daysBack = $dayOfWeek + 3;
        }
        
        $deliveryWeekThursday = date('Y-m-d', strtotime($targetDate . " -$daysBack days"));
        
        // Get week number based on Thursday (delivery week start)
        $deliveryWeekNumber = (int) date('W', strtotime($deliveryWeekThursday));
        
        // Use subscription ID to determine if customer is in A or B group
        // Swap groups since payment is taken in advance
        // Even subscription IDs = Group B, Odd subscription IDs = Group A
        $subscriptionGroup = (intval($subscriptionId) % 2 === 0) ? 'B' : 'A';
        
        return $subscriptionGroup;
    }

    /**
     * Check if a delivery/collection should happen this week based on frequency
     * 
     * @param string $frequency 'weekly' or 'fortnightly' 
     * @param string $subscriptionId Subscription ID for A/B group assignment
     * @param string $targetDate Date to check
     * @return bool
     */
    public function shouldDeliverThisWeek($frequency, $subscriptionId, $targetDate = null)
    {
        if (strtolower($frequency) === 'weekly') {
            return true; // Weekly deliveries happen every week
        }

        if (strtolower($frequency) === 'fortnightly') {
            if (!$targetDate) {
                $targetDate = date('Y-m-d');
            }
            
            // Get the Thursday of the current delivery week
            $targetTimestamp = strtotime($targetDate);
            $dayOfWeek = date('N', $targetTimestamp); // 1=Monday, 4=Thursday, 7=Sunday
            
            // Calculate days to subtract to get to Thursday
            if ($dayOfWeek >= 4) {
                // If Thu/Fri/Sat/Sun, go back to this week's Thursday
                $daysBack = $dayOfWeek - 4;
            } else {
                // If Mon/Tue/Wed, go back to last week's Thursday
                $daysBack = $dayOfWeek + 3;
            }
            
            $deliveryWeekThursday = date('Y-m-d', strtotime($targetDate . " -$daysBack days"));
            
            // Get delivery week number based on Thursday
            $deliveryWeekNumber = (int) date('W', strtotime($deliveryWeekThursday));
            
            // Use subscription ID to determine if customer is in A or B group
            // Swap groups since payment is taken in advance
            // Even subscription IDs = Group B, Odd subscription IDs = Group A
            $subscriptionGroup = (intval($subscriptionId) % 2 === 0) ? 'B' : 'A';
            
            // Current delivery week determines which group delivers this week
            // Even delivery weeks = Group A delivers, Odd delivery weeks = Group B delivers
            $currentWeekGroup = ($deliveryWeekNumber % 2 === 0) ? 'A' : 'B';
            
            // Customer should deliver if they're in the current delivery week's group
            return $subscriptionGroup === $currentWeekGroup;
        }

        return true; // Default to true for unknown frequencies
    }

    /**
     * Get delivery schedule with enhanced frequency logic
     */
    public function getEnhancedSchedule($startDate = null, $endDate = null, $status = 'active')
    {
        $scheduleData = $this->getSchedule($startDate, $endDate, $status);
        
        if (!$scheduleData || !isset($scheduleData['data'])) {
            return $scheduleData;
        }

        // Enhance each delivery/collection with frequency information
        foreach ($scheduleData['data'] as $date => &$dateData) {
            // Process deliveries
            if (isset($dateData['deliveries'])) {
                foreach ($dateData['deliveries'] as &$delivery) {
                    $this->enhanceWithFrequencyInfo($delivery, $date);
                }
            }

            // Process collections
            if (isset($dateData['collections'])) {
                foreach ($dateData['collections'] as &$collection) {
                    $this->enhanceWithFrequencyInfo($collection, $date);
                }
            }
        }

        return $scheduleData;
    }

    /**
     * Enhance delivery/collection item with frequency information
     */
    private function enhanceWithFrequencyInfo(&$item, $deliveryDate)
    {
        // Use frequency from API response if available, otherwise extract from products
        $frequency = $item['frequency'] ?? $this->extractFrequencyFromProducts($item['products'] ?? []);
        
        // Get subscription ID for A/B group calculation
        $subscriptionId = $item['id'] ?? 0;
        
        // Add frequency information
        $item['frequency'] = $frequency;
        $item['week_type'] = $this->calculateWeekAB($subscriptionId, $deliveryDate);
        $item['should_deliver'] = $this->shouldDeliverThisWeek($frequency, $subscriptionId, $deliveryDate);
        
        // Add visual indicators
        $item['frequency_badge'] = strtolower($frequency) === 'weekly' ? 'primary' : 'info';
        $item['week_badge'] = $item['week_type'] === 'A' ? 'success' : 'warning';
    }

    /**
     * Extract frequency from product names or descriptions
     */
    private function extractFrequencyFromProducts($products)
    {
        foreach ($products as $product) {
            $name = strtolower($product['name'] ?? '');
            
            // Look for frequency indicators in product names
            if (strpos($name, 'fortnightly') !== false) {
                return 'fortnightly';
            }
            if (strpos($name, 'weekly') !== false) {
                return 'weekly';
            }
            // Check for annual products (special case)
            if (strpos($name, 'annual') !== false) {
                return 'annual';
            }
        }
        
        // Default to weekly if not specified
        return 'weekly';
    }

    /**
     * Transform WooCommerce subscriptions into delivery schedule format
     */
    private function transformSubscriptionsToSchedule($subscriptions)
    {
        $schedule = [
            'success' => true,
            'data' => []
        ];

        foreach ($subscriptions as $subscription) {
            // Get customer data
            $customer = $subscription['billing'] ?? [];
            $shipping = $subscription['shipping'] ?? [];
            
            // Get subscription items (products)
            $items = [];
            foreach ($subscription['line_items'] ?? [] as $item) {
                $items[] = [
                    'name' => $item['name'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'] ?? 0
                ];
            }
            
            // Get next payment date for delivery scheduling
            $nextPaymentDate = $subscription['next_payment_date'] ?? null;
            $deliveryDate = $nextPaymentDate ? date('Y-m-d', strtotime($nextPaymentDate)) : date('Y-m-d');
            
            // Get billing period (weekly, fortnightly, monthly)
            $frequency = $this->getSubscriptionFrequency($subscription);
            
            // Build customer data
            $customerData = [
                'id' => $subscription['customer_id'],
                'name' => trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? '')),
                'email' => $customer['email'] ?? '',
                'phone' => $customer['phone'] ?? '',
                'address' => $this->formatAddress($shipping ?: $customer),
                'products' => $items,
                'frequency' => $frequency,
                'frequency_badge' => $this->getFrequencyBadge($frequency),
                'status' => $subscription['status'],
                'subscription_id' => $subscription['id'],
                'total' => $subscription['total'] ?? 0
            ];
            
            // Add frequency-specific data
            if (strtolower($frequency) === 'fortnightly') {
                $customerData['week_type'] = $this->getFortnightlyWeekType($subscription['id'], $deliveryDate);
                $customerData['should_deliver'] = $this->shouldDeliverFortnightly($subscription['id'], $deliveryDate);
                $customerData['week_badge'] = $customerData['should_deliver'] ? 'success' : 'secondary';
            }
            
            // Initialize date array if not exists
            if (!isset($schedule['data'][$deliveryDate])) {
                $schedule['data'][$deliveryDate] = [
                    'deliveries' => [],
                    'collections' => []
                ];
            }
            
            // Add to deliveries (assuming all subscriptions are deliveries for now)
            $schedule['data'][$deliveryDate]['deliveries'][] = $customerData;
        }
        
        return $schedule;
    }
    
    /**
     * Transform WooCommerce orders into delivery schedule format
     */
    private function transformOrdersToSchedule($orders)
    {
        $schedule = [
            'success' => true,
            'data' => []
        ];

        foreach ($orders as $order) {
            // Skip orders that aren't relevant for delivery scheduling
            if (!in_array($order['status'], ['processing', 'completed', 'on-hold'])) {
                continue;
            }

            // Get customer data
            $customer = $order['billing'] ?? [];
            $shipping = $order['shipping'] ?? [];
            
            // Get order items (products)
            $items = [];
            foreach ($order['line_items'] ?? [] as $item) {
                $items[] = [
                    'name' => $item['name'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'] ?? 0
                ];
            }
            
            // Use order date for delivery scheduling
            $deliveryDate = date('Y-m-d', strtotime($order['date_created']));
            
            // Build customer data for delivery schedule
            $customerData = [
                'id' => $order['customer_id'],
                'name' => trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? '')),
                'email' => $customer['email'] ?? '',
                'phone' => $customer['phone'] ?? '',
                'address' => $this->formatAddress($shipping ?: $customer),
                'products' => $items,
                'frequency' => 'One-time', // Orders are typically one-time
                'frequency_badge' => 'secondary',
                'status' => $order['status'],
                'order_id' => $order['id'],
                'total' => $order['total'] ?? 0
            ];
            
            // Initialize date array if not exists
            if (!isset($schedule['data'][$deliveryDate])) {
                $schedule['data'][$deliveryDate] = [
                    'deliveries' => [],
                    'collections' => []
                ];
            }
            
            // Add to deliveries (assuming orders are deliveries)
            $schedule['data'][$deliveryDate]['deliveries'][] = $customerData;
        }
        
        return $schedule;
    }

    /**
     * Get subscription billing frequency
     */
    private function getSubscriptionFrequency($subscription)
    {
        $billingPeriod = $subscription['billing_period'] ?? 'week';
        $billingInterval = $subscription['billing_interval'] ?? 1;
        
        if ($billingPeriod === 'week') {
            return $billingInterval == 1 ? 'Weekly' : 'Fortnightly';
        } elseif ($billingPeriod === 'month') {
            return 'Monthly';
        }
        
        return 'Weekly';
    }
    
    /**
     * Get frequency badge color
     */
    private function getFrequencyBadge($frequency)
    {
        switch (strtolower($frequency)) {
            case 'weekly':
                return 'success';
            case 'fortnightly':
                return 'warning';
            case 'monthly':
                return 'info';
            default:
                return 'secondary';
        }
    }
    
    /**
     * Format address array into readable format
     */
    private function formatAddress($addressData)
    {
        $address = [];
        
        if (!empty($addressData['address_1'])) {
            $address[] = $addressData['address_1'];
        }
        if (!empty($addressData['address_2'])) {
            $address[] = $addressData['address_2'];
        }
        if (!empty($addressData['city'])) {
            $address[] = $addressData['city'];
        }
        if (!empty($addressData['state'])) {
            $address[] = $addressData['state'];
        }
        if (!empty($addressData['postcode'])) {
            $address[] = $addressData['postcode'];
        }
        if (!empty($addressData['country'])) {
            $address[] = $addressData['country'];
        }
        
        return $address;
    }

    /**
     * Get fortnightly week type (A or B) for a subscription
     */
    private function getFortnightlyWeekType($subscriptionId, $targetDate = null)
    {
        if (!$targetDate) {
            $targetDate = date('Y-m-d');
        }
        
        // Use subscription ID to determine if customer is in A or B group
        // Even subscription IDs = Group B, Odd subscription IDs = Group A
        return (intval($subscriptionId) % 2 === 0) ? 'B' : 'A';
    }

    /**
     * Check if fortnightly customer should deliver this week
     */
    private function shouldDeliverFortnightly($userId, $targetDate = null)
    {
        if (!$targetDate) {
            $targetDate = date('Y-m-d');
        }
        
        $weekType = $this->getFortnightlyWeekType($userId, $targetDate);
        $currentWeek = date('W', strtotime($targetDate));
        $currentWeekGroup = ($currentWeek % 2 === 0) ? 'A' : 'B';
        
        return $weekType === $currentWeekGroup;
    }
}
