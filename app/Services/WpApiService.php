<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

class WpApiService
{
    protected $apiUrl;
    protected $apiKey;
    protected $apiSecret;
    protected $wcApiUrl;
    protected $wcConsumerKey;
    protected $wcConsumerSecret;
    protected $integrationKey;

    public function __construct()
    {
        $this->apiUrl = config('services.wp_api.url');
        $this->apiKey = config('services.wp_api.key');
        $this->apiSecret = config('services.wp_api.secret');
        // WooCommerce API and Integration key
        $this->wcApiUrl        = config('services.wc_api.url');
        $this->wcConsumerKey   = config('services.wc_api.consumer_key');
        $this->wcConsumerSecret= config('services.wc_api.consumer_secret');
        $this->integrationKey  = config('services.wc_api.integration_key');
    }

    /**
     * Get the API URL
     */
    public function getApiUrl()
    {
        return $this->apiUrl;
    }

    /**
     * Test API connection
     */
    public function testConnection()
    {
        try {
            // Test WooCommerce API connection first
            $response = Http::withBasicAuth($this->apiKey, $this->apiSecret)
                ->get("{$this->apiUrl}/wp-json/wc/v3/system_status");
            
            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true, 
                    'woocommerce' => true,
                    'version' => $data['environment']['version'] ?? 'unknown'
                ];
            }
            
            // Fallback to basic wp-json test
            $response = Http::withBasicAuth($this->apiKey, $this->apiSecret)
                ->get("{$this->apiUrl}/wp-json");
                
            return [
                'success' => $response->successful(),
                'woocommerce' => false,
                'status_code' => $response->status()
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false, 
                'message' => $e->getMessage(),
                'woocommerce' => false
            ];
        }
    }

    /**
     * Search users via MWF integration plugin
     */
    public function searchUsers($query, $limit = 20)
    {
        try {
            $response = Http::withHeaders([
                'X-WC-API-Key' => $this->integrationKey
            ])->get("{$this->apiUrl}/wp-json/mwf/v1/users/search", [
                'q' => $query, 
                'limit' => $limit
                // Temporarily remove role filter to see if user exists with different role
                // 'role' => 'customer'
            ]);
            
            $data = $response->json();
            
            // Check if the response has the expected structure
            if (isset($data['success']) && $data['success'] && isset($data['users'])) {
                return collect($data['users']);
            }
            
            Log::warning('User search returned unexpected format', ['response' => $data]);
            return collect();
        } catch (Exception $e) {
            Log::error('User search failed: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Search users by email address
     */
    public function searchUsersByEmail($email, $limit = 20)
    {
        try {
            // First try the MWF endpoint if available
            $response = Http::withHeaders([
                'X-WC-API-Key' => $this->integrationKey
            ])->get("{$this->apiUrl}/wp-json/mwf/v1/users/search", [
                'q' => $email, 
                'limit' => $limit
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                
                // Check if the response has the expected structure
                if (isset($data['success']) && $data['success'] && isset($data['users'])) {
                    // Filter to exact email match - check both 'email' and 'user_email' fields
                    $users = collect($data['users'])->filter(function($user) use ($email) {
                        $userEmail = $user['email'] ?? $user['user_email'] ?? '';
                        return strtolower($userEmail) === strtolower($email);
                    });
                    return $users->values()->toArray();
                }
            }
            
            // Fallback to WooCommerce customers API
            $response = Http::withBasicAuth($this->wcConsumerKey, $this->wcConsumerSecret)
                ->get("{$this->wcApiUrl}/wp-json/wc/v3/customers", [
                    'search' => $email,
                    'per_page' => $limit
                ]);
            
            if ($response->successful()) {
                $customers = $response->json();
                // Filter to exact email match and convert to user format
                $users = collect($customers)->filter(function($customer) use ($email) {
                    return strtolower($customer['email'] ?? '') === strtolower($email);
                })->map(function($customer) {
                    return [
                        'id' => $customer['id'],
                        'user_email' => $customer['email'],
                        'display_name' => $customer['first_name'] . ' ' . $customer['last_name'],
                        'first_name' => $customer['first_name'],
                        'last_name' => $customer['last_name']
                    ];
                });
                
                return $users->values()->toArray();
            }
            
            return [];
            
        } catch (Exception $e) {
            Log::error('User search by email failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get user details by ID via API
     */
    public function getUserById($userId)
    {
        try {
            $response = Http::withHeaders([
                'X-WC-API-Key' => $this->integrationKey
            ])->get("{$this->apiUrl}/wp-json/mwf/v1/users/{$userId}");
            
            $data = $response->json();
            
            // Check if the response has the expected structure
            if (isset($data['success']) && $data['success'] && isset($data['user'])) {
                return $data['user'];
            }
            
            Log::warning('Get user returned unexpected format', [
                'user_id' => $userId,
                'response' => $data
            ]);
            return null;
        } catch (Exception $e) {
            Log::error('Get user failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get recent users via API
     */
    public function getRecentUsers($limit = 10, $role = 'customer')
    {
        try {
            $params = ['limit' => $limit];
            if ($role) {
                $params['role'] = $role;
            }

            $response = Http::withHeaders([
                'X-WC-API-Key' => $this->integrationKey
            ])->get("{$this->apiUrl}/wp-json/mwf/v1/users/recent", $params);
            
            $data = $response->json();
            
            // Check if the response has the expected structure
            if (isset($data['success']) && $data['success'] && isset($data['users'])) {
                return collect($data['users']);
            }
            
            Log::warning('Get recent users returned unexpected format', ['response' => $data]);
            return collect();
        } catch (Exception $e) {
            Log::error('Get recent users failed: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Get user funds balance - DISABLED
     * Account Funds plugin removed from system
     */
    public function getUserFunds($email)
    {
        return 0;
    }

    /**
     * Generate user switch URL via MWF integration plugin
     */
    public function generateUserSwitchUrl($userId, $redirectTo = '/my-account/', $adminContext = 'laravel_admin')
    {
        try {
            $response = Http::withHeaders([
                'X-WC-API-Key' => $this->integrationKey
            ])->post("{$this->apiUrl}/wp-json/mwf/v1/users/switch", [
                'user_id' => $userId,
                'redirect_to' => $redirectTo,
                'admin_context' => $adminContext,
                'clear_session' => true,  // Force session cleanup
                'auto_logout' => true     // Automatically logout previous user first
            ]);

            $data = $response->json();
            
            // Check if the response has the expected structure
            if (isset($data['success']) && $data['success'] && isset($data['preview_url'])) {
                return $data['preview_url'];
            }
            
            Log::warning('User switch returned unexpected format', [
                'user_id' => $userId,
                'response' => $data
            ]);
            return null;
        } catch (Exception $e) {
            Log::error('Generate switch URL failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Add funds to user account - DISABLED
     * Account Funds plugin removed from system
     */
    public function addUserFunds($email, $amount)
    {
        return ['success' => false, 'message' => 'Account Funds system has been disabled'];
    }

    /**
     * Create a new WooCommerce order via API
     */
    public function createOrder(array $orderData)
    {
        try {
            $response = Http::withBasicAuth($this->wcConsumerKey, $this->wcConsumerSecret)
                ->post("{$this->wcApiUrl}/wp-json/wc/v3/orders", $orderData);
            return $response->json();
        } catch (Exception $e) {
            Log::error('Create order failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get subscription payment status via MWF plugin
     */
    public function getSubscriptionPaymentStatus($subscriptionId)
    {
        try {
            $response = Http::withBasicAuth($this->apiKey, $this->apiSecret)
                ->get("{$this->apiUrl}/wp-json/mwf/v1/subscription-payment-status", ['subscription_id' => $subscriptionId]);
            return $response->json();
        } catch (Exception $e) {
            Log::error('Get subscription payment status failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Process a subscription payment via MWF plugin
     */
    public function processSubscriptionPayment($subscriptionId, array $params)
    {
        try {
            $payload = array_merge($params, ['subscription_id' => $subscriptionId]);
            $response = Http::withBasicAuth($this->apiKey, $this->apiSecret)
                ->post("{$this->apiUrl}/wp-json/mwf/v1/subscription-payment/process", $payload);
            return $response->json();
        } catch (Exception $e) {
            Log::error('Process subscription payment failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get delivery schedule data via direct database query
     * 
     * IMPORTANT: This method uses DIRECT DATABASE ACCESS instead of WooCommerce REST API
     * because the /wc/v3/subscriptions endpoint is only available with the WooCommerce 
     * Subscriptions paid plugin. This approach works with WooCommerce core only.
     */
    public function getDeliveryScheduleData($limit = 100)
    {
        try {
            // Fetch active subscriptions directly from WordPress database (works without paid plugin)
            // Filter to active statuses to match old REST API behavior
            $subscriptions = \App\Models\WooCommerceOrder::subscriptions()
                ->whereIn('post_status', ['wc-active', 'wc-pending', 'wc-on-hold'])
                ->with(['meta', 'items', 'items.meta'])
                ->orderBy('post_date', 'desc')
                ->limit($limit)
                ->get();
            
            // Transform to match the REST API response format expected by DeliveryController
            $data = [];
            
            foreach ($subscriptions as $subscription) {
                // Build subscription data in the same format as WooCommerce REST API
                $subData = [
                    'id' => $subscription->ID,
                    'customer_id' => (int) $subscription->getMeta('_customer_user'), // WordPress user ID
                    'status' => $this->normalizeSubscriptionStatus($subscription->post_status),
                    'date_created' => $subscription->post_date,
                    'date_modified' => $subscription->post_modified,
                    'parent_id' => $subscription->post_parent,
                    
                    // Billing details from metadata
                    'billing' => [
                        'first_name' => $subscription->getMeta('_billing_first_name'),
                        'last_name' => $subscription->getMeta('_billing_last_name'),
                        'company' => $subscription->getMeta('_billing_company'),
                        'address_1' => $subscription->getMeta('_billing_address_1'),
                        'address_2' => $subscription->getMeta('_billing_address_2'),
                        'city' => $subscription->getMeta('_billing_city'),
                        'state' => $subscription->getMeta('_billing_state'),
                        'postcode' => $subscription->getMeta('_billing_postcode'),
                        'country' => $subscription->getMeta('_billing_country'),
                        'email' => $subscription->getMeta('_billing_email'),
                        'phone' => $subscription->getMeta('_billing_phone'),
                    ],
                    
                    // Shipping details from metadata
                    'shipping' => [
                        'first_name' => $subscription->getMeta('_shipping_first_name'),
                        'last_name' => $subscription->getMeta('_shipping_last_name'),
                        'company' => $subscription->getMeta('_shipping_company'),
                        'address_1' => $subscription->getMeta('_shipping_address_1'),
                        'address_2' => $subscription->getMeta('_shipping_address_2'),
                        'city' => $subscription->getMeta('_shipping_city'),
                        'state' => $subscription->getMeta('_shipping_state'),
                        'postcode' => $subscription->getMeta('_shipping_postcode'),
                        'country' => $subscription->getMeta('_shipping_country'),
                    ],
                    
                    // Subscription schedule metadata
                    'billing_period' => $subscription->getMeta('_billing_period'),
                    'billing_interval' => $subscription->getMeta('_billing_interval'),
                    'next_payment_date' => $subscription->getMeta('_schedule_next_payment'),
                    'trial_end_date' => $subscription->getMeta('_schedule_trial_end'),
                    'end_date' => $subscription->getMeta('_schedule_end'),
                    
                    // Financial data
                    'total' => $subscription->getMeta('_order_total'),
                    'shipping_total' => $subscription->getMeta('_order_shipping'),
                    'currency' => $subscription->getMeta('_order_currency') ?: 'GBP',
                    
                    // Payment method
                    'payment_method' => $subscription->getMeta('_payment_method'),
                    'payment_method_title' => $subscription->getMeta('_payment_method_title'),
                    
                    // Custom metadata for delivery schedule
                    'customer_week_type' => $subscription->getMeta('customer_week_type'),
                    'preferred_collection_day' => $subscription->getMeta('preferred_collection_day'),
                    
                    // Meta data array (for compatibility with REST API format)
                    'meta_data' => [],
                    
                    // Line items (products)
                    'line_items' => [],
                ];
                
                // Add custom metadata to meta_data array if present
                $customerWeekType = $subscription->getMeta('customer_week_type');
                if ($customerWeekType) {
                    $subData['meta_data'][] = [
                        'key' => 'customer_week_type',
                        'value' => $customerWeekType,
                    ];
                }
                
                $preferredCollectionDay = $subscription->getMeta('preferred_collection_day');
                if ($preferredCollectionDay) {
                    $subData['meta_data'][] = [
                        'key' => 'preferred_collection_day',
                        'value' => $preferredCollectionDay,
                    ];
                }
                
                // Add line items from order items
                foreach ($subscription->items as $item) {
                    $subData['line_items'][] = [
                        'id' => $item->order_item_id,
                        'name' => $item->order_item_name,
                        'product_id' => $item->getMeta('_product_id'),
                        'variation_id' => $item->getMeta('_variation_id'),
                        'quantity' => $item->getMeta('_qty'),
                        'subtotal' => $item->getMeta('_line_subtotal'),
                        'total' => $item->getMeta('_line_total'),
                        'meta_data' => $item->meta->map(function($meta) {
                            return [
                                'key' => $meta->meta_key,
                                'value' => $meta->meta_value,
                            ];
                        })->toArray(),
                    ];
                }
                
                $data[] = $subData;
            }
            
            Log::info('Fetched delivery schedule data from database', [
                'count' => count($data),
                'method' => 'direct_database_query'
            ]);
            
            return $data;
            
        } catch (\Exception $e) {
            Log::error('Get delivery schedule failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }
    
    /**
     * Normalize WooCommerce subscription status from database format (wc-active) 
     * to REST API format (active)
     */
    private function normalizeSubscriptionStatus($status)
    {
        $statusMap = [
            'wc-active' => 'active',
            'wc-pending' => 'pending',
            'wc-on-hold' => 'on-hold',
            'wc-cancelled' => 'cancelled',
            'wc-expired' => 'expired',
            'wc-pending-cancel' => 'pending-cancel',
            'wc-switched' => 'switched',
        ];
        
        return $statusMap[$status] ?? str_replace('wc-', '', $status);
    }

    /**
     * Get user meta information from WordPress
     * 
     * @param int $userId The WordPress user ID
     * @param string $metaKey Optional specific meta key to retrieve
     * @return array|string|null The meta value(s)
     */
    public function getUserMeta($userId, $metaKey = null)
    {
        try {
            $params = ['user_id' => $userId];
            
            // Add specific meta key if provided
            if ($metaKey) {
                $params['meta_key'] = $metaKey;
            }
            
            $response = Http::withHeaders(['X-WC-API-Key' => $this->integrationKey])
                ->get("{$this->apiUrl}/wp-json/mwf/v1/user-meta", $params);
                
            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['success']) && $data['success']) {
                    if ($metaKey) {
                        return $data['meta_value'] ?? null;
                    }
                    return $data['meta'] ?? [];
                }
            }
            
            // Fallback to WP REST API for user meta
            $response = Http::withBasicAuth($this->wcConsumerKey, $this->wcConsumerSecret)
                ->get("{$this->apiUrl}/wp-json/wp/v2/users/{$userId}");
                
            if ($response->successful()) {
                $userData = $response->json();
                
                // For specific meta keys like preferred_collection_day
                if ($metaKey === 'preferred_collection_day') {
                    return $userData['meta']['preferred_collection_day'][0] ?? 'Friday'; // Default to Friday
                }
                
                // Return all meta if available
                return $userData['meta'] ?? [];
            }
            
            return null;
        } catch (Exception $e) {
            Log::error('Get user meta failed: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Public method for making raw API requests (for debugging)
     */
    public function makeRequest($method, $endpoint, $params = [])
    {
        // Create cache key from method, endpoint, and params
        $cacheKey = 'wp_api_' . md5($method . $endpoint . serialize($params));
        $cacheTtl = 300; // 5 minutes cache for API responses
        
        // Only cache GET requests
        if ($method === 'GET') {
            $cached = cache($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }
        
        try {
            $url = "{$this->apiUrl}/wp-json/{$endpoint}";
            
            // Determine authentication method based on endpoint
            if (strpos($endpoint, 'mwf/') === 0) {
                // Use X-WC-API-Key for MWF endpoints
                $httpClient = Http::withHeaders(['X-WC-API-Key' => $this->integrationKey]);
            } else {
                // Use basic auth for WooCommerce endpoints
                $httpClient = Http::withBasicAuth($this->apiKey, $this->apiSecret);
            }
            
            if ($method === 'GET') {
                $response = $httpClient->get($url, $params);
            } else {
                $response = $httpClient->send($method, $url, ['json' => $params]);
            }
            
            $result = [
                'status_code' => $response->status(),
                'successful' => $response->successful(),
                'data' => $response->json(),
                'headers' => $response->headers()
            ];
            
            // Cache successful GET responses
            if ($method === 'GET' && $result['successful']) {
                cache([$cacheKey => $result], $cacheTtl);
            }
            
            return $result;
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Enrich subscription data with customer billing/shipping details
     */
    private function enrichSubscriptionsWithCustomerData($subscriptions)
    {
        // Collect unique customer IDs
        $customerIds = array_unique(array_column($subscriptions, 'customer_id'));
        
        // Batch fetch customer data
        $customers = [];
        foreach ($customerIds as $customerId) {
            if ($customerId > 0) {
                $customer = $this->getCustomerData($customerId);
                if ($customer) {
                    $customers[$customerId] = $customer;
                }
            }
        }
        
        // Enrich subscriptions with customer data
        foreach ($subscriptions as &$subscription) {
            $customerId = $subscription['customer_id'] ?? 0;
            
            if (isset($customers[$customerId])) {
                $customer = $customers[$customerId];
                
                // Add billing data if missing
                if (empty($subscription['billing']) && isset($customer['billing'])) {
                    $subscription['billing'] = $customer['billing'];
                }
                
                // Add shipping data if missing
                if (empty($subscription['shipping']) && isset($customer['shipping'])) {
                    $subscription['shipping'] = $customer['shipping'];
                }
            }
        }
        
        return $subscriptions;
    }
    
    /**
     * Get customer data including billing/shipping
     */
    private function getCustomerData($customerId)
    {
        try {
            $response = Http::withBasicAuth($this->wcConsumerKey, $this->wcConsumerSecret)
                ->get("{$this->wcApiUrl}/wp-json/wc/v3/customers/{$customerId}");
            
            if ($response->successful()) {
                return $response->json();
            }
            
            return null;
        } catch (Exception $e) {
            Log::warning("Failed to get customer data for ID {$customerId}: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Authenticate admin user with WordPress
     * Creates a WordPress admin session for seamless integration
     */
    public function authenticateAdminWithWordPress($adminEmail, $adminName)
    {
        try {
            Log::info('Attempting WordPress authentication for admin', ['email' => $adminEmail]);
            
            // First, check if the admin user exists in WordPress
            $wpUser = $this->findOrCreateWordPressUser($adminEmail, $adminName);
            
            if (!$wpUser) {
                Log::warning('Failed to find or create WordPress user', ['email' => $adminEmail]);
                return ['success' => false, 'error' => 'Could not create WordPress user'];
            }
            
            // Create WordPress authentication cookie/session
            $authResult = $this->createWordPressSession($wpUser);
            
            if ($authResult['success']) {
                Log::info('WordPress authentication successful', ['email' => $adminEmail, 'wp_user_id' => $wpUser['id']]);
                return [
                    'success' => true,
                    'wp_user' => $wpUser,
                    'wp_auth_cookie' => $authResult['auth_cookie'] ?? null,
                    'wp_admin_url' => $this->getWordPressAdminUrl()
                ];
            } else {
                Log::warning('WordPress session creation failed', ['email' => $adminEmail]);
                return ['success' => false, 'error' => 'Could not create WordPress session'];
            }
            
        } catch (Exception $e) {
            Log::error('WordPress authentication error', ['email' => $adminEmail, 'error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Find or create a WordPress user for admin access
     */
    private function findOrCreateWordPressUser($email, $name)
    {
        try {
            // Check if there's an email mapping for this Laravel admin
            $emailMapping = config('admin_users.wordpress_email_mapping', []);
            $wordpressEmail = $emailMapping[$email] ?? $email;
            
            if ($wordpressEmail !== $email) {
                Log::info('Using email mapping for WordPress authentication', [
                    'laravel_email' => $email,
                    'wordpress_email' => $wordpressEmail
                ]);
            }
            
            // Use the mapped email to search for WordPress user
            Log::info('Searching for WordPress user using MWF API', ['email' => $wordpressEmail]);
            
            $response = Http::withHeaders([
                'X-WC-API-Key' => $this->integrationKey
            ])->get("{$this->apiUrl}/wp-json/mwf/v1/users/search", [
                'q' => $wordpressEmail,
                'limit' => 5
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['success']) && $data['success'] && isset($data['users'])) {
                    // Filter to exact email match
                    foreach ($data['users'] as $user) {
                        if (strtolower($user['email'] ?? '') === strtolower($wordpressEmail)) {
                            Log::info('Found existing WordPress user via MWF API', ['email' => $wordpressEmail, 'wp_user_id' => $user['id']]);
                            // Convert MWF user format to WordPress user format
                            return [
                                'id' => $user['id'],
                                'email' => $user['email'],
                                'display_name' => $user['display_name'] ?? $name,
                                'username' => $user['username'] ?? $user['email']
                            ];
                        }
                    }
                }
            }
            
            // Fallback: try the original WordPress API approach
            Log::info('MWF API search failed, trying WordPress core API', ['email' => $wordpressEmail]);
            
            $response = Http::withBasicAuth($this->apiKey, $this->apiSecret)
                ->get("{$this->apiUrl}/wp-json/wp/v2/users", [
                    'search' => $wordpressEmail
                ]);
            
            if ($response->successful()) {
                $users = $response->json();
                // Check if user exists with this email
                foreach ($users as $user) {
                    if (strtolower($user['email']) === strtolower($wordpressEmail)) {
                        Log::info('Found existing WordPress user via core API', ['email' => $wordpressEmail, 'wp_user_id' => $user['id']]);
                        return $user;
                    }
                }
            }
            
            // User doesn't exist, return null (do NOT create)
            Log::warning('WordPress user not found in either API', ['email' => $wordpressEmail]);
            return null;
        } catch (Exception $e) {
            Log::error('Error finding WordPress user', ['email' => $email, 'error' => $e->getMessage()]);
            return null;
        }
    }
    
    /**
     * Create WordPress authentication session
     */
    private function createWordPressSession($wpUser)
    {
        try {
            // Use a custom endpoint or WordPress REST API extension to create auth session
            // This might require a custom WordPress plugin to handle authentication
            
            $response = Http::withBasicAuth($this->apiKey, $this->apiSecret)
                ->post("{$this->apiUrl}/wp-json/mwf/v1/auth/admin-login", [
                    'user_id' => $wpUser['id'],
                    'user_email' => $wpUser['email'],
                    'admin_source' => 'mwf_admin_panel'
                ]);
            
            if ($response->successful()) {
                $authData = $response->json();
                return [
                    'success' => true,
                    'auth_cookie' => $authData['auth_cookie'] ?? null,
                    'session_token' => $authData['session_token'] ?? null
                ];
            } else {
                // Fallback: just mark as successful for now
                // The main goal is to ensure the WP user exists
                Log::info('WordPress session endpoint not available, user exists', ['wp_user_id' => $wpUser['id']]);
                return ['success' => true];
            }
            
        } catch (Exception $e) {
            Log::warning('WordPress session creation failed, but user exists', ['error' => $e->getMessage()]);
            // Return success anyway - the main goal is user existence
            return ['success' => true];
        }
    }
    
    /**
     * Get WordPress admin URL
     */
    private function getWordPressAdminUrl()
    {
        $baseUrl = str_replace('/wp-json', '', $this->apiUrl);
        return $baseUrl . '/wp-admin/';
    }
    
    /**
     * Get subscription URL for a customer using WordPress endpoint
     */
    public function getSubscriptionUrl($email, $customerName = '')
    {
        try {
            Log::info('Starting getSubscriptionUrl', ['email' => $email, 'customer_name' => $customerName]);
            
            // First try the WordPress endpoint if available
            $response = Http::timeout(30)
                ->withHeaders([
                    'X-WC-API-Key' => $this->integrationKey,
                    'Content-Type' => 'application/json'
                ])
                ->post($this->apiUrl . '/wp-json/mwf/v1/admin/subscription-redirect', [
                    'email' => $email,
                    'customer_name' => $customerName
                ]);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('WordPress endpoint successful', ['data' => $data]);
                return [
                    'success' => true,
                    'subscription_url' => $data['url'] ?? null,
                    'user_id' => $data['user_id'] ?? null,
                    'customer_name' => $data['customer_name'] ?? $customerName,
                    'customer_email' => $data['customer_email'] ?? $email
                ];
            }

            // If WordPress endpoint fails (404 or other error), fall back to direct URL generation
            if ($response->status() === 404) {
                Log::info('WordPress subscription-redirect endpoint not available, using fallback method');
                $fallbackResult = $this->getSubscriptionUrlFallback($email, $customerName);
                Log::info('Fallback result received', ['fallback_result' => $fallbackResult]);
                return $fallbackResult;
            }

            Log::info('WordPress endpoint failed with status', ['status' => $response->status()]);
            return [
                'success' => false,
                'error' => 'WordPress API request failed: ' . $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('WordPress get subscription URL failed: ' . $e->getMessage());
            // Try fallback method on exception
            Log::info('Calling fallback method due to exception');
            $fallbackResult = $this->getSubscriptionUrlFallback($email, $customerName);
            Log::info('Fallback result received from exception', ['fallback_result' => $fallbackResult]);
            return $fallbackResult;
        }
    }

    /**
     * Fallback method to generate subscription URL when WordPress endpoint is not available
     */
    private function getSubscriptionUrlFallback($email, $customerName = '')
    {
        try {
            Log::info('Using fallback method for subscription URL', ['email' => $email]);
            
            // Try to find the user via search method
            $user = $this->searchUsersByEmail($email);
            
            Log::info('Search results for user in fallback', ['email' => $email, 'user_count' => count($user), 'user_data' => $user]);
            
            if (!empty($user)) {
                $userId = $user[0]['id'] ?? null;
                
                Log::info('Found user in fallback', ['user_id' => $userId, 'user_data' => $user[0]]);
                
                if ($userId) {
                    // Generate authenticated URL using the Laravel application as a proxy
                    // This will redirect through our admin panel which can handle authentication
                    $subscriptionUrl = config('app.url') . '/admin/user-switching/subscription-redirect/' . $userId;
                    
                    Log::info('Generated subscription URL for found user', ['user_id' => $userId, 'url' => $subscriptionUrl]);
                    
                    return [
                        'success' => true,
                        'subscription_url' => $subscriptionUrl,
                        'user_id' => $userId,
                        'customer_name' => $customerName ?: ($user[0]['display_name'] ?? ''),
                        'customer_email' => $email
                    ];
                } else {
                    Log::warning('User found but no ID', ['user_data' => $user[0]]);
                }
            }
            
            // If user not found, try to create a search URL to find them
            $searchUrl = str_replace('/wp-json', '', $this->apiUrl) . '/wp-admin/users.php?s=' . urlencode($email);
            
            Log::info('User not found, generating search URL', ['email' => $email, 'search_url' => $searchUrl]);
            
            return [
                'success' => true,
                'subscription_url' => $searchUrl,
                'user_id' => null,
                'customer_name' => $customerName,
                'customer_email' => $email,
                'note' => 'User not found in system. This will search for the user in WordPress admin.'
            ];
            
        } catch (\Exception $e) {
            Log::error('Fallback subscription URL generation failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to generate subscription URL: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get user profile URL for a customer using WordPress endpoint
     */
    public function getUserProfileUrl($email, $customerName = '')
    {
        try {
            Log::info('Starting getUserProfileUrl', ['email' => $email, 'customer_name' => $customerName]);
            
            // First try the WordPress endpoint if available
            $response = Http::timeout(30)
                ->withHeaders([
                    'X-WC-API-Key' => $this->integrationKey,
                    'Content-Type' => 'application/json'
                ])
                ->post($this->apiUrl . '/wp-json/mwf/v1/admin/user-profile-redirect', [
                    'email' => $email,
                    'customer_name' => $customerName
                ]);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('WordPress profile endpoint successful', ['data' => $data]);
                return [
                    'success' => true,
                    'profile_url' => $data['url'] ?? null,
                    'user_id' => $data['user_id'] ?? null,
                    'customer_name' => $data['customer_name'] ?? $customerName,
                    'customer_email' => $data['customer_email'] ?? $email
                ];
            }

            // If WordPress endpoint fails (404 or other error), fall back to direct URL generation
            if ($response->status() === 404) {
                Log::info('WordPress user-profile-redirect endpoint not available, using fallback method');
                $fallbackResult = $this->getUserProfileUrlFallback($email, $customerName);
                Log::info('Profile fallback result received', ['fallback_result' => $fallbackResult]);
                return $fallbackResult;
            }

            Log::info('WordPress profile endpoint failed with status', ['status' => $response->status()]);
            return [
                'success' => false,
                'error' => 'WordPress API request failed: ' . $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('WordPress get profile URL failed: ' . $e->getMessage());
            // Try fallback method on exception
            Log::info('Calling profile fallback method due to exception');
            $fallbackResult = $this->getUserProfileUrlFallback($email, $customerName);
            Log::info('Profile fallback result received from exception', ['fallback_result' => $fallbackResult]);
            return $fallbackResult;
        }
    }

    /**
     * Fallback method to generate profile URL when WordPress endpoint is not available
     */
    private function getUserProfileUrlFallback($email, $customerName = '')
    {
        try {
            Log::info('Using fallback method for profile URL', ['email' => $email]);
            
            // Try to find the user via search method
            $user = $this->searchUsersByEmail($email);
            
            Log::info('Search results for user in profile fallback', ['email' => $email, 'user_count' => count($user), 'user_data' => $user]);
            
            if (!empty($user)) {
                $userId = $user[0]['id'] ?? null;
                
                Log::info('Found user in profile fallback', ['user_id' => $userId, 'user_data' => $user[0]]);
                
                if ($userId) {
                    // Generate direct WordPress user profile URL
                    $profileUrl = str_replace('/wp-json', '', $this->apiUrl) . '/wp-admin/user-edit.php?user_id=' . $userId;
                    
                    Log::info('Generated direct WordPress profile URL for found user', ['user_id' => $userId, 'url' => $profileUrl]);
                    
                    return [
                        'success' => true,
                        'profile_url' => $profileUrl,
                        'user_id' => $userId,
                        'customer_name' => $customerName ?: ($user[0]['display_name'] ?? ''),
                        'customer_email' => $email
                    ];
                } else {
                    Log::warning('User found but no ID in profile fallback', ['user_data' => $user[0]]);
                }
            }
            
            // If user not found, try to create a search URL to find them
            $searchUrl = str_replace('/wp-json', '', $this->apiUrl) . '/wp-admin/users.php?s=' . urlencode($email);
            
            Log::info('User not found, generating search URL for profile', ['email' => $email, 'search_url' => $searchUrl]);
            
            return [
                'success' => true,
                'profile_url' => $searchUrl,
                'user_id' => null,
                'customer_name' => $customerName,
                'customer_email' => $email,
                'note' => 'User not found in system. This will search for the user in WordPress admin.'
            ];
            
        } catch (\Exception $e) {
            Log::error('Fallback profile URL generation failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to generate profile URL: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get customer count from WooCommerce API
     */
    public function getCustomerCount()
    {
        try {
            // Use WooCommerce REST API to get customers
            $response = Http::withBasicAuth($this->wcConsumerKey, $this->wcConsumerSecret)
                ->get("{$this->wcApiUrl}/wp-json/wc/v3/customers", [
                    'per_page' => 1, // Just get one to check if API works
                    'page' => 1
                ]);
            
            if ($response->successful()) {
                $data = $response->json();
                
                // Get total count from response headers
                $totalCount = $response->header('X-WP-Total');
                
                if ($totalCount !== null) {
                    return intval($totalCount);
                }
                
                // Fallback: count the returned items
                return is_array($data) ? count($data) : 0;
            } else {
                Log::error('WooCommerce API failed for customers', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return 0;
            }
            
        } catch (\Exception $e) {
            Log::error('Error fetching customer count: ' . $e->getMessage());
            return 0;
        }
    }
}
