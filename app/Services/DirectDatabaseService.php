<?php

namespace App\Services;

use App\Models\WooCommerceOrder;
use App\Models\WordPressUser;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DirectDatabaseService
{
    protected $wpConnection;
    protected $apiUrl;
    protected $apiKey;
    protected $apiSecret;

    public function __construct()
    {
        // Use WordPress REST API
        $this->apiUrl = config('services.wp_api.url');
        $this->apiKey = config('services.wp_api.key');
        $this->apiSecret = config('services.wp_api.secret');
        
        // Set WordPress database connection
        $this->wpConnection = 'wordpress';
    }

    /**
     * Test API connection
     */
    public function testConnection()
    {
        try {
            $response = Http::withBasicAuth($this->apiKey, $this->apiSecret)
                ->get("{$this->apiUrl}/wp-json");
            return ['success' => $response->successful()];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Search users via API with database fallback
     */
    public function searchUsers($query, $limit = 20)
    {
        try {
            $response = Http::withBasicAuth($this->apiKey, $this->apiSecret)
                ->get("{$this->apiUrl}/wp-json/mwf/v1/users/search", ['query' => $query, 'limit' => $limit]);
            
            if ($response->successful()) {
                return collect($response->json());
            }
        } catch (Exception $e) {
            Log::error('User search API failed: ' . $e->getMessage());
        }

        // Fallback to database query
        try {
            $users = WordPressUser::where('user_login', 'like', "%{$query}%")
                ->orWhere('user_email', 'like', "%{$query}%")
                ->orWhere('display_name', 'like', "%{$query}%")
                ->limit($limit)
                ->get()
                ->map(function ($user) {
                    return [
                        'ID' => $user->ID,
                        'user_login' => $user->user_login,
                        'user_email' => $user->user_email,
                        'display_name' => $user->display_name,
                        'user_registered' => $user->user_registered,
                    ];
                });
            
            return $users;
        } catch (Exception $e) {
            Log::error('User search database fallback failed: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Get user by ID via API with database fallback
     */
    public function getUserById($userId)
    {
        try {
            $response = Http::withBasicAuth($this->apiKey, $this->apiSecret)
                ->get("{$this->apiUrl}/wp-json/mwf/v1/users/{$userId}");
            
            if ($response->successful()) {
                return $response->json();
            }
        } catch (Exception $e) {
            Log::error('Get user API failed: ' . $e->getMessage());
        }

        // Fallback to database query
        try {
            $user = WordPressUser::find($userId);
            if ($user) {
                return [
                    'ID' => $user->ID,
                    'user_login' => $user->user_login,
                    'user_email' => $user->user_email,
                    'display_name' => $user->display_name,
                    'user_registered' => $user->user_registered,
                    'user_status' => $user->user_status,
                ];
            }
        } catch (Exception $e) {
            Log::error('Get user database fallback failed: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Get recent users via API
     */
    public function getRecentUsers($limit = 10, $role = null)
    {
        try {
            $params = ['limit' => $limit];
            if ($role) $params['role'] = $role;
            $response = Http::withBasicAuth($this->apiKey, $this->apiSecret)
                ->get("{$this->apiUrl}/wp-json/mwf/v1/users/recent", $params);
            return collect($response->json());
        } catch (Exception $e) {
            Log::error('Get recent users failed: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Generate user switch URL via API
     */
    public function generateUserSwitchUrl($userId, $redirectTo = '/my-account/')
    {
        try {
            $response = Http::withBasicAuth($this->apiKey, $this->apiSecret)
                ->post("{$this->apiUrl}/wp-json/mwf/v1/users/switch", ['id' => $userId, 'redirect_to' => $redirectTo]);
            $data = $response->json();
            return $data['switch_url'] ?? null;
        } catch (Exception $e) {
            Log::error('Generate switch URL failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get WordPress options (settings)
     */
    public function getOption($optionName, $default = null)
    {
        try {
            $result = DB::connection($this->wpConnection)
                ->table('options')
                ->where('option_name', $optionName)
                ->first();
            
            return $result ? $result->option_value : $default;
        } catch (Exception $e) {
            Log::error('Get option failed: ' . $e->getMessage());
            return $default;
        }
    }

    /**
     * Get WooCommerce settings
     */
    public function getWooCommerceSettings()
    {
        try {
            return [
                'currency' => $this->getOption('woocommerce_currency', 'GBP'),
                'currency_symbol' => $this->getOption('woocommerce_currency_symbol', '£'),
                'store_address' => $this->getOption('woocommerce_store_address'),
                'store_city' => $this->getOption('woocommerce_store_city'),
                'store_postcode' => $this->getOption('woocommerce_store_postcode'),
            ];
        } catch (Exception $e) {
            Log::error('Get WooCommerce settings failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get user orders - direct database query
     */
    public function getUserOrders($userId, $limit = 10)
    {
        try {
            $orders = WooCommerceOrder::orders()
                ->whereHas('meta', function ($query) use ($userId) {
                    $query->where('meta_key', '_customer_user')
                          ->where('meta_value', $userId);
                })
                ->orderBy('post_date', 'desc')
                ->limit($limit)
                ->get();

            return $orders->map(function ($order) {
                return $order->getFormattedData();
            });
        } catch (Exception $e) {
            Log::error('Get user orders failed: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Get user subscriptions - direct database query
     */
    public function getUserSubscriptions($userId, $limit = 10)
    {
        try {
            $subscriptions = WooCommerceOrder::subscriptions()
                ->whereHas('meta', function ($query) use ($userId) {
                    $query->where('meta_key', '_customer_user')
                          ->where('meta_value', $userId);
                })
                ->orderBy('post_date', 'desc')
                ->limit($limit)
                ->get();

            return $subscriptions->map(function ($subscription) {
                return $subscription->getFormattedData();
            });
        } catch (Exception $e) {
            Log::error('Get user subscriptions failed: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Get user funds/balance - simplified implementation
     * In a real system, this would connect to your payment/wallet system
     */
    public function getUserFunds($userEmail)
    {
        try {
            // For now, return 0 as we don't have funds system in direct database
            // This would need to be connected to your actual funds/wallet system
            return 0.00;
        } catch (Exception $e) {
            Log::error('Get user funds failed: ' . $e->getMessage());
            return 0.00;
        }
    }

    /**
     * Generate user switching URL using the WordPress User Switching plugin
     */
    public function switchToUser($userId, $redirectTo = '/my-account/', $context = 'admin_panel')
    {
        try {
            // Validate user exists in WordPress database
            $user = DB::connection($this->wpConnection)
                ->table('users')
                ->where('ID', $userId)
                ->first();

            if (!$user) {
                Log::warning('User not found for switching', ['user_id' => $userId]);
                return null;
            }

            // Generate the admin switch key (same logic as WordPress)
            $secret = 'mwf_admin_switch_2025_secret_key';
            $adminKey = hash('sha256', $userId . $redirectTo . $secret);

            // Create the switch URL using our custom WordPress endpoint
            $baseUrl = rtrim(env('WOOCOMMERCE_URL', 'https://middleworldfarms.org'), '/');
            $ajaxUrl = $baseUrl . '/wp-admin/admin-ajax.php?' . http_build_query([
                'action' => 'mwf_generate_plugin_switch_url',
                'user_id' => $userId,
                'redirect_to' => $redirectTo,
                'admin_key' => $adminKey
            ]);

            // Make HTTP request to WordPress to get the actual auto-login URL
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $ajaxUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false); // Don't follow redirects
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($response === false || $httpCode !== 200) {
                Log::error('Failed to fetch switch URL from WordPress', [
                    'ajax_url' => $ajaxUrl,
                    'http_code' => $httpCode
                ]);
                return null;
            }

            // Parse JSON response
            $responseData = json_decode($response, true);
            
            if (!$responseData || !isset($responseData['success']) || !$responseData['success']) {
                Log::error('WordPress returned error response', [
                    'response' => $response,
                    'ajax_url' => $ajaxUrl
                ]);
                return null;
            }

            if (!isset($responseData['data']['switch_url'])) {
                Log::error('No switch_url in WordPress response', ['response' => $responseData]);
                return null;
            }

            $finalSwitchUrl = $responseData['data']['switch_url'];

            Log::info('Generated user switch URL successfully', [
                'user_id' => $userId,
                'user_login' => $user->user_login,
                'redirect_to' => $redirectTo,
                'ajax_url' => $ajaxUrl,
                'final_switch_url' => $finalSwitchUrl
            ]);

            return $finalSwitchUrl;

        } catch (\Exception $e) {
            Log::error('Error generating switch URL', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return null;
        }
    }

    /**
     * Authenticate WordPress user
     */
    public function authenticateWPUser($email, $password)
    {
        try {
            $user = DB::connection($this->wpConnection)
                ->table('users')
                ->where('user_email', $email)
                ->first();

            if (!$user) {
                return null;
            }

            // Check password using WordPress hash
            if (!$this->checkWPPassword($password, $user->user_pass)) {
                return null;
            }

            // Get user capabilities
            $capabilities = DB::connection($this->wpConnection)
                ->table('usermeta')
                ->where('user_id', $user->ID)
                ->where('meta_key', env('WP_DB_PREFIX', 'wp_') . 'capabilities')
                ->value('meta_value');

            return [
                'ID' => $user->ID,
                'user_login' => $user->user_login,
                'user_email' => $user->user_email,
                'display_name' => $user->display_name,
                'capabilities' => $capabilities
            ];

        } catch (Exception $e) {
            Log::error('WordPress authentication failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Check WordPress password hash
     */
    private function checkWPPassword($password, $hash)
    {
        // WordPress uses MD5 and phpass
        if (strlen($hash) <= 32) {
            return hash_equals($hash, md5($password));
        }

        // For newer WordPress installations with stronger hashing
        // This is a simplified check - in production you might want to use
        // the actual WordPress password checking functions
        return password_verify($password, $hash);
    }

    /**
     * Calculate current fortnightly week (A or B)
     * Odd ISO week numbers = Week A, Even ISO week numbers = Week B
     */
    private function calculateFortnightlyWeek()
    {
        $currentWeek = (int) date('W'); // ISO week number
        return ($currentWeek % 2 === 1) ? 'A' : 'B';
    }

    /**
     * Determine if a fortnightly subscription should have delivery this week
     */
    private function isFortnightlyDeliveryWeek($subscription)
    {
        try {
            // First, check if there's an assigned week in the meta
            $assignedWeek = $subscription->getMeta('_mwf_fortnightly_week');
            
            if ($assignedWeek) {
                // If we have an assigned week (A or B), use that
                $currentWeek = $this->calculateFortnightlyWeek();
                return $assignedWeek === $currentWeek;
            }
            
            // Fallback: Use subscription start date to determine its Week A/B cycle
            $startDate = $subscription->post_date;
            $startWeek = (int) date('W', strtotime($startDate));
            
            // Determine if subscription started on Week A or Week B
            $subscriptionStartWeek = ($startWeek % 2 === 1) ? 'A' : 'B';
            
            // Current week
            $currentWeek = $this->calculateFortnightlyWeek();
            
            // Check if current week matches subscription's cycle
            return $subscriptionStartWeek === $currentWeek;
            
        } catch (Exception $e) {
            Log::warning('Could not determine fortnightly delivery week', [
                'subscription_id' => $subscription->ID ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            
            // Default to true if we can't determine
            return true;
        }
    }

    /**
     * Get fortnightly delivery schedule for a specific week
     */
    public function getFortnightlySchedule($weekType = null)
    {
        if (!$weekType) {
            $weekType = $this->calculateFortnightlyWeek();
        }
        
        try {
            $fortnightlySubscriptions = WooCommerceOrder::subscriptions()
                ->active()
                ->with(['meta'])
                ->whereIn('post_status', ['wc-active', 'wc-on-hold'])
                ->get()
                ->filter(function ($subscription) use ($weekType) {
                    // Check MWF fortnightly meta field (correct field that exists)
                    $mwfFortnightly = $subscription->getMeta('_mwf_fortnightly');
                    
                    if ($mwfFortnightly !== 'yes') {
                        return false;
                    }
                    
                    return $this->isFortnightlyDeliveryWeek($subscription);
                })
                ->map(function ($subscription) use ($weekType) {
                    $data = $subscription->getFormattedData();
                    $data['frequency'] = 'Fortnightly';
                    $data['frequency_badge'] = 'warning'; // Orange badge
                    $data['delivery_week'] = $weekType;
                    $data['assigned_week'] = $subscription->getMeta('_mwf_fortnightly_week') ?: 'A';
                    $data['type'] = 'fortnightly_subscription';
                    return $data;
                });

            return [
                'week_type' => $weekType,
                'current_iso_week' => date('W'),
                'subscriptions' => $fortnightlySubscriptions->values(),
                'count' => $fortnightlySubscriptions->count()
            ];
            
        } catch (Exception $e) {
            Log::error('Failed to get fortnightly schedule', [
                'week_type' => $weekType,
                'error' => $e->getMessage()
            ]);
            
            return [
                'week_type' => $weekType,
                'current_iso_week' => date('W'),
                'subscriptions' => collect(),
                'count' => 0,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get count of weekly subscriptions
     */
    public function getWeeklySubscriptionsCount()
    {
        try {
            $weeklyCount = WooCommerceOrder::subscriptions()
                ->active()
                ->with(['meta'])
                ->whereIn('post_status', ['wc-active', 'wc-on-hold'])
                ->get()
                ->filter(function ($subscription) {
                    // Check MWF fortnightly meta field - if not "yes", it's weekly
                    $mwfFortnightly = $subscription->getMeta('_mwf_fortnightly');
                    return $mwfFortnightly !== 'yes';
                })
                ->count();
            
            return $weeklyCount;
            
        } catch (Exception $e) {
            Log::error('Failed to get weekly subscriptions count', [
                'error' => $e->getMessage()
            ]);
            
            return 0;
        }
    }

    /**
     * Get frequency badge color based on frequency type
     */
    private function getFrequencyBadge($frequency)
    {
        switch (strtolower($frequency)) {
            case 'weekly':
                return 'success';
            case 'fortnightly':
                return 'warning';
            default:
                return 'secondary';
        }
    }

    /**
     * Get WordPress option from database
     */
    private function getWordPressOption($optionName)
    {
        try {
            $result = DB::connection($this->wpConnection)->table('options')
                ->where('option_name', $optionName)
                ->value('option_value');
            
            return $result;
        } catch (Exception $e) {
            Log::error('Failed to get WordPress option', [
                'option' => $optionName,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}
