<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\WpApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserSwitchingController extends Controller
{
    protected WpApiService $wpApi;

    public function __construct(WpApiService $wpApi)
    {
        $this->wpApi = $wpApi;
    }

    /**
     * Display the user switching interface
     */
    public function index(Request $request)
    {
        // Use the same logic as CustomerManagementController for better spam filtering
        $recentUsers = [];
        $searchResults = [];
        $searchQuery = '';
        $debug = [];
        
        try {
            $baseUrl = config('services.wordpress.api_base');
            $apiKey = config('services.wordpress.api_key');
            $page = $request->input('page', 1);
            $perPage = $request->input('per_page', 20);
            $search = $request->input('search', '');
            $searchQuery = $search;
            
            if (!$baseUrl || !$apiKey) {
                $debug['error'] = 'Missing MWF API configuration';
                return view('admin.user-switching.index', [
                    'recentUsers' => $recentUsers,
                    'searchResults' => $searchResults,
                    'searchQuery' => $searchQuery,
                    'debug' => $debug
                ]);
            }
            
            $endpoint = $baseUrl . '/users/list';
            
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'X-API-Key' => $apiKey,
            ])->get($endpoint, [
                'q' => $search,
                'role' => 'customer', // Back to 'customer' - WooCommerce customers
                'page' => $page,
                'per_page' => $perPage,
            ]);

            $data = $response->json();
            $debug['endpoint'] = $endpoint;
            $debug['total_users_from_api'] = isset($data['users']) ? count($data['users']) : 0;
            
            if ($data && isset($data['success']) && $data['success'] && isset($data['users']) && is_array($data['users'])) {
                $filteredUsers = [];
                $spamCount = 0;
                $noOrdersCount = 0;
                
                foreach ($data['users'] as $user) {
                    // Filter out spam/test accounts using the same logic as CustomerManagementController
                    $email = $user['email'] ?? '';
                    $name = $user['display_name'] ?? '';
                    $isSpam = false;
                    
                    if (
                        empty($email) ||
                        preg_match('/@(qq|emaily\.pro|vtext\.com|txt\.att\.net|vzwpix\.com|att\.net|hotmail\.com)$/i', $email) ||
                        preg_match('/^\d+@/', $email) ||
                        preg_match('/^\./', $email) ||
                        stripos($email, 'test') !== false ||
                        stripos($name, 'test') !== false
                    ) {
                        $isSpam = true;
                        $spamCount++;
                    }
                    
                    $orderCount = $user['wc_data']['order_count'] ?? 0;
                    $subscribed = false;
                    if (isset($user['subscriptions']) && is_array($user['subscriptions'])) {
                        foreach ($user['subscriptions'] as $sub) {
                            if (isset($sub['status']) && $sub['status'] === 'active') {
                                $subscribed = true;
                                break;
                            }
                        }
                    }
                    
                    // Only include customers who have actually bought something or have active subscriptions
                    if (!$isSpam && ($orderCount > 0 || $subscribed)) {
                        $filteredUsers[] = [
                            'id' => $user['id'],
                            'display_name' => $name,
                            'user_login' => $user['username'] ?? $name,
                            'user_email' => $email,
                            'last_activity' => $user['recent_orders'][0]['date'] ?? 'Unknown',
                            'subscribed' => $subscribed,
                            'order_count' => $orderCount,
                        ];
                    } elseif (!$isSpam && $orderCount == 0 && !$subscribed) {
                        $noOrdersCount++;
                    }
                }
                
                $debug['filtered_users_count'] = count($filteredUsers);
                $debug['spam_filtered_out'] = $spamCount;
                $debug['no_orders_filtered_out'] = $noOrdersCount;
                $debug['filter_criteria'] = 'Only showing users with orders > 0 OR active subscriptions';
                
                if ($search) {
                    $searchResults = $filteredUsers;
                } else {
                    $recentUsers = $filteredUsers;
                }
            } else {
                $debug['error'] = 'Invalid response from MWF endpoint.';
            }
        } catch (\Exception $e) {
            $debug['exception'] = $e->getMessage();
        }

        return view('admin.user-switching.index', compact(
            'recentUsers',
            'searchResults', 
            'searchQuery'
        ))->with('debug', $debug ?? []);
    }

    /**
     * Search users via AJAX
     */
    public function search(Request $request)
    {
        $query = $request->get('q', '');
        $limit = $request->get('limit', 20);

        if (empty($query)) {
            return response()->json([
                'success' => false,
                'error' => 'Search query is required'
            ]);
        }

        $users = $this->wpApi->searchUsers($query, $limit);

        return response()->json([
            'success' => true,
            'users' => $users,
            'count' => count($users)
        ]);
    }

    /**
     * Get user details
     */
    public function getUserDetails($userId)
    {
        $user = $this->wpApi->getUserById($userId);

        if (!$user) {
            return response()->json([
                'success' => false,
                'error' => 'User not found'
            ]);
        }

        // Get user funds balance
        $funds = 0;
        if (!empty($user['email'])) {
            $funds = $this->wpApi->getUserFunds($user['email']);
        }

        $user['account_funds'] = $funds;

        return response()->json([
            'success' => true,
            'user' => $user
        ]);
    }

    /**
     * Switch to a user
     */
    public function switchToUser(Request $request, $userId)
    {
        try {
            // Validate user ID
            if (!$userId || !is_numeric($userId)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid user ID provided'
                ], 400);
            }

            $redirectTo = $request->get('redirect_to', '/my-account/');
            
            $switchUrl = $this->wpApi->generateUserSwitchUrl(
                $userId, 
                $redirectTo,
                'laravel_admin_panel'
            );

            if (!$switchUrl) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to generate switch URL - user may not exist or API connection failed'
                ], 400);
            }

            \Log::info("User switch successful", [
                'user_id' => $userId,
                'redirect_to' => $redirectTo,
                'switch_url' => substr($switchUrl, 0, 50) . '...'
            ]);

            return response()->json([
                'success' => true,
                'switch_url' => $switchUrl,
                'message' => 'Switch URL generated successfully'
            ]);
        } catch (\Exception $e) {
            \Log::error("User switch failed", [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Switch to user and redirect (for direct links)
     */
    public function switchAndRedirect($userId, Request $request)
    {
        $redirectTo = $request->get('redirect_to', '/my-account/');
        
        $switchUrl = $this->wpApi->generateUserSwitchUrl(
            $userId,
            $redirectTo,
            'laravel_admin_direct'
        );

        if (!$switchUrl) {
            return redirect()->back()->with('error', 'Failed to switch to user');
        }

        // Redirect to the switch URL
        return redirect($switchUrl);
    }

    /**
     * Get recent users for dashboard widget
     */
    public function getRecentUsers(Request $request)
    {
        $limit = $request->get('limit', 10);
        $users = $this->wpApi->getRecentUsers($limit);

        return response()->json([
            'success' => true,
            'users' => $users,
            'count' => count($users)
        ]);
    }

    /**
     * Switch to user by email (for delivery schedule integration)
     */
    public function switchByEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'customer_name' => 'sometimes|string'
        ]);

        $email = $request->input('email');
        $customerName = $request->input('customer_name', 'Customer');
        $redirectTo = $request->input('redirect_to', '/my-account/');

        try {
            // First, search for the user by exact email
            Log::info("UserSwitching: Starting search", [
                'email' => $email,
                'customer_name' => $customerName
            ]);
            
            // First try API search
            $users = $this->wpApi->searchUsers($email, 1);
            
            Log::info("UserSwitching: API search results", [
                'email' => $email,
                'users_found' => $users ? $users->count() : 0,
                'users_data' => $users ? $users->toArray() : null
            ]);
            
            // Fallback to database if API returns empty
            if (empty($users) || $users->isEmpty()) {
                Log::info("UserSwitching: API search failed, trying direct database lookup");
                $wpUser = \App\Models\WordPressUser::where('user_email', $email)->first();
                
                if ($wpUser) {
                    // Convert to array format expected by rest of code
                    $users = collect([[
                        'id' => $wpUser->ID,
                        'email' => $wpUser->user_email,
                        'username' => $wpUser->user_login,
                        'display_name' => $wpUser->display_name,
                    ]]);
                    Log::info("UserSwitching: Found user in database", ['user_id' => $wpUser->ID]);
                }
            }
            
            if (empty($users) || $users->isEmpty()) {
                // Fallback 1: Try searching by customer name if provided
                if (!empty($customerName) && $customerName !== 'Customer') {
                    Log::info("Email not found, trying name search", ['email' => $email, 'name' => $customerName]);
                    $users = $this->wpApi->searchUsers($customerName, 5);
                    
                    if (!empty($users) && $users->count() > 0) {
                        // Found users by name, use the first one
                        $user = $users->first();
                        $foundBy = "name '{$customerName}'";
                    }
                }
                
                // Fallback 2: Try partial email search (remove domain)
                if ((empty($users) || $users->isEmpty()) && strpos($email, '@') !== false) {
                    $emailParts = explode('@', $email);
                    $username = $emailParts[0];
                    Log::info("Name search failed, trying username search", ['username' => $username]);
                    $users = $this->wpApi->searchUsers($username, 5);
                    
                    if (!empty($users) && $users->count() > 0) {
                        $user = $users->first();
                        $foundBy = "username '{$username}'";
                    }
                }
                
                // If still no users found, return helpful error
                if (empty($users) || $users->isEmpty()) {
                    return response()->json([
                        'success' => false,
                        'error' => "No WordPress user found for email: {$email}",
                        'suggestion' => "This customer email may not have a WordPress user account. Try creating a user account for this customer first."
                    ]);
                }
            } else {
                $user = $users->first();
                $foundBy = "email '{$email}'";
            }
            $userId = $user['id'] ?? null;

            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'error' => "Invalid user data for email: {$email}"
                ]);
            }

            // Generate switch URL using the user ID
            $switchUrl = $this->wpApi->generateUserSwitchUrl(
                $userId,
                $redirectTo,
                'delivery_schedule_admin'
            );

            if (!$switchUrl) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to generate switch URL'
                ]);
            }

            return response()->json([
                'success' => true,
                'switch_url' => $switchUrl,
                'message' => "Switch URL generated for {$customerName} (found by {$foundBy})",
                'user' => $user,
                'found_by' => $foundBy ?? 'email'
            ]);

        } catch (\Exception $e) {
            Log::error('Error switching user by email', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Server error while switching user'
            ]);
        }
    }

    /**
     * Redirect to user switching
     */
    public function redirect(int $userId)
    {
        $switchUrl = $this->wpApi->switchToUser(
            $userId,
            '/my-account/',
            'admin_panel'
        );

        if (!$switchUrl) {
            return redirect()->back()->with('error', 'Failed to switch to user');
        }

        return redirect($switchUrl);
    }

    /**
     * Get subscription URL for a customer
     */
    public function getSubscriptionUrl(Request $request)
    {
        try {
            $email = $request->input('email');
            $customerName = $request->input('customer_name', '');

            if (empty($email)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Email address is required'
                ], 400);
            }

            // Use the new WordPress endpoint to get subscription URL
            $result = $this->wpApi->getSubscriptionUrl($email, $customerName);
            
            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'subscription_url' => $result['subscription_url'],
                    'customer_name' => $customerName,
                    'customer_email' => $email,
                    'user_id' => $result['user_id'] ?? null
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $result['error'] ?? 'Failed to get subscription URL',
                    'subscription_url' => null
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Get subscription URL failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Server error: ' . $e->getMessage(),
                'subscription_url' => null
            ], 500);
        }
    }

    /**
     * Redirect to customer subscription page in WordPress
     */
    public function subscriptionRedirect($userId)
    {
        try {
            // Get user details to verify they exist
            $userDetails = $this->wpApi->getUserById($userId);
            
            if (!$userDetails) {
                return redirect()->route('admin.deliveries.index')
                    ->with('error', 'Customer not found');
            }

            $customerEmail = $userDetails['user_email'] ?? '';
            $customerName = $userDetails['display_name'] ?? 'Customer';
            
            // Get current admin user info and apply email mapping
            $currentAdmin = auth()->user();
            $adminEmail = $currentAdmin->email;
            $adminName = $currentAdmin->name;
            
            // Apply WordPress email mapping if configured
            $emailMapping = config('admin_users.wordpress_email_mapping', []);
            $wordpressEmail = $emailMapping[$adminEmail] ?? $adminEmail;
            
            Log::info('Subscription redirect using email mapping', [
                'customer_email' => $customerEmail,
                'customer_name' => $customerName,
                'laravel_email' => $adminEmail,
                'wordpress_email' => $wordpressEmail
            ]);
            
            // Try to get an authenticated subscription URL from WordPress
            $subscriptionResult = $this->wpApi->getAuthenticatedSubscriptionUrl($customerEmail, $customerName, $wordpressEmail);
            
            Log::info('Subscription redirect result', [
                'subscription_result' => $subscriptionResult,
                'subscription_url_from_endpoint' => $subscriptionResult['subscription_url'] ?? null
            ]);
            
            if ($subscriptionResult['success'] && !empty($subscriptionResult['subscription_url'])) {
                // We have a valid authenticated subscription URL
                $finalUrl = $subscriptionResult['subscription_url'];
                
                Log::info('About to redirect to subscription URL', [
                    'final_url' => $finalUrl,
                    'customer_name' => $customerName,
                    'url_starts_with' => substr($finalUrl, 0, 100)
                ]);
                
                return redirect()->away($finalUrl)
                    ->with('success', 'Redirecting to customer subscription for ' . $customerName);
            } else {
                // No subscription URL available, show information page
                return view('admin.customer-subscription-access', [
                    'customer_name' => $customerName,
                    'customer_email' => $customerEmail,
                    'user_id' => $userId,
                    'subscription_url' => null,
                    'error' => $subscriptionResult['error'] ?? 'Could not generate subscription URL'
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Subscription redirect failed: ' . $e->getMessage());
            
            return redirect()->route('admin.deliveries.index')
                ->with('error', 'Failed to access customer subscription: ' . $e->getMessage());
        }
    }
    
    /**
     * Get customer subscription information via WooCommerce API
     */
    private function getCustomerSubscriptionInfo($userId)
    {
        try {
            // Try to get subscriptions for this customer
            $subscriptions = $this->wpApi->getDeliveryScheduleData(100);
            
            $customerSubscriptions = collect($subscriptions)->filter(function($subscription) use ($userId) {
                return ($subscription['customer_id'] ?? 0) == $userId;
            });
            
            return [
                'count' => $customerSubscriptions->count(),
                'subscriptions' => $customerSubscriptions->take(5)->map(function($sub) {
                    return [
                        'id' => $sub['id'] ?? null,
                        'status' => $sub['status'] ?? 'unknown',
                        'total' => $sub['total'] ?? '0',
                        'next_payment' => $sub['next_payment_date'] ?? null,
                        'products' => collect($sub['line_items'] ?? [])->pluck('name')->join(', ')
                    ];
                })->toArray()
            ];
            
        } catch (\Exception $e) {
            Log::error('Failed to get customer subscription info: ' . $e->getMessage());
            return [
                'count' => 0,
                'subscriptions' => [],
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get user profile URL for a customer
     */
    public function getUserProfileUrl(Request $request)
    {
        try {
            $email = $request->input('email');
            $customerName = $request->input('customer_name', '');

            if (empty($email)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Email address is required'
                ], 400);
            }

            // Use the WordPress endpoint to get profile URL
            $result = $this->wpApi->getUserProfileUrl($email, $customerName);
            
            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'profile_url' => $result['profile_url'],
                    'customer_name' => $customerName,
                    'customer_email' => $email,
                    'user_id' => $result['user_id'] ?? null
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $result['error'] ?? 'Failed to get profile URL',
                    'profile_url' => null
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Get profile URL failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Server error: ' . $e->getMessage(),
                'profile_url' => null
            ], 500);
        }
    }

    /**
     * Redirect to customer profile page in WordPress
     */
    public function profileRedirect($userId)
    {
        try {
            // Get user details to verify they exist
            $userDetails = $this->wpApi->getUserById($userId);
            
            if (!$userDetails) {
                return redirect()->route('admin.deliveries.index')
                    ->with('error', 'Customer not found');
            }

            $customerEmail = $userDetails['user_email'] ?? '';
            $customerName = $userDetails['display_name'] ?? 'Customer';
            
            Log::info('Profile redirect debug', [
                'customer_email' => $customerEmail,
                'customer_name' => $customerName,
                'user_id' => $userId
            ]);
            
            // Try to get the profile URL using the WordPress endpoint
            $profileResult = $this->wpApi->getUserProfileUrl($customerEmail, $customerName);
            
            Log::info('Profile result from endpoint', [
                'profile_result' => $profileResult,
                'profile_url_from_endpoint' => $profileResult['profile_url'] ?? null
            ]);
            
            if ($profileResult['success'] && !empty($profileResult['profile_url'])) {
                // We have a valid profile URL, but need to authenticate first
                
                // Get current admin user info
                $currentAdmin = auth()->user();
                $adminEmail = $currentAdmin->email;
                $adminName = $currentAdmin->name;
                
                // Apply WordPress email mapping if configured
                $emailMapping = config('admin_users.wordpress_email_mapping', []);
                $wordpressEmail = $emailMapping[$adminEmail] ?? $adminEmail;
                
                Log::info('Profile: Using WordPress email mapping', [
                    'laravel_email' => $adminEmail,
                    'wordpress_email' => $wordpressEmail
                ]);
                
                // Try to authenticate with WordPress using mapped email
                $authResult = $this->wpApi->authenticateAdminWithWordPress($wordpressEmail, $adminName);
                
                if ($authResult['success']) {
                    // Authentication successful, redirect to profile URL
                    $finalUrl = $profileResult['profile_url'];
                    
                    Log::info('About to redirect to profile URL', [
                        'final_url' => $finalUrl,
                        'customer_name' => $customerName,
                        'url_starts_with' => substr($finalUrl, 0, 100)
                    ]);
                    
                    return redirect()->away($finalUrl)
                        ->with('success', 'Redirecting to customer profile for ' . $customerName);
                } else {
                    // Authentication failed, show manual access page
                    return view('admin.customer-profile-access', [
                        'customer_name' => $customerName,
                        'customer_email' => $customerEmail,
                        'user_id' => $userId,
                        'profile_url' => $profileResult['profile_url'],
                        'auth_error' => $authResult['error'] ?? 'WordPress authentication failed',
                        'manual_access_needed' => true
                    ]);
                }
            } else {
                // No profile URL available from endpoint, fall back to direct construction
                Log::info('Profile endpoint failed, using fallback method', [
                    'error' => $profileResult['error'] ?? 'Unknown error'
                ]);
                
                // Get current admin user info
                $currentAdmin = auth()->user();
                $adminEmail = $currentAdmin->email;
                $adminName = $currentAdmin->name;
                
                // Apply WordPress email mapping if configured
                $emailMapping = config('admin_users.wordpress_email_mapping', []);
                $wordpressEmail = $emailMapping[$adminEmail] ?? $adminEmail;
                
                Log::info('Profile fallback: Using WordPress email mapping', [
                    'laravel_email' => $adminEmail,
                    'wordpress_email' => $wordpressEmail
                ]);
                
                // Try to authenticate with WordPress using mapped email
                $authResult = $this->wpApi->authenticateAdminWithWordPress($wordpressEmail, $adminName);
                
                if ($authResult['success']) {
                    // Authentication successful, redirect to WordPress user profile URL
                    $profileUrl = str_replace('/wp-json', '', $this->wpApi->getApiUrl()) . '/wp-admin/user-edit.php?user_id=' . $userId;
                    
                    Log::info('About to redirect to fallback profile URL', [
                        'final_url' => $profileUrl,
                        'customer_name' => $customerName,
                        'user_id' => $userId
                    ]);
                    
                    return redirect()->away($profileUrl)
                        ->with('success', 'Redirecting to customer profile for ' . $customerName);
                } else {
                    // Authentication failed, show manual access page
                    $profileUrl = str_replace('/wp-json', '', $this->wpApi->getApiUrl()) . '/wp-admin/user-edit.php?user_id=' . $userId;
                    
                    return view('admin.customer-profile-access', [
                        'customer_name' => $customerName,
                        'customer_email' => $customerEmail,
                        'user_id' => $userId,
                        'profile_url' => $profileUrl,
                        'auth_error' => $authResult['error'] ?? 'WordPress authentication failed',
                        'manual_access_needed' => true
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::error('Profile redirect failed: ' . $e->getMessage());
            
            return redirect()->route('admin.deliveries.index')
                ->with('error', 'Failed to access customer profile: ' . $e->getMessage());
        }
    }

    /**
     * Test API connection
     */
    public function test()
    {
        try {
            // Test WordPress API connection
            $wpTest = $this->wpApi->testConnection();
            
            $results = [
                'timestamp' => now()->format('Y-m-d H:i:s'),
                'wordpress_api' => $wpTest,
            ];
            
            // Try to get a sample user count
            try {
                $userCount = $this->wpApi->getUserCount();
                $results['user_count'] = $userCount;
            } catch (\Exception $e) {
                $results['user_count_error'] = $e->getMessage();
            }
            
            // Try to get recent users
            try {
                $recentUsers = $this->wpApi->getRecentUsers(5);
                $results['recent_users_sample'] = count($recentUsers);
                $results['sample_users'] = array_map(function($user) {
                    return [
                        'id' => $user['id'] ?? 'N/A',
                        'email' => $user['email'] ?? 'N/A',
                        'display_name' => $user['display_name'] ?? 'N/A'
                    ];
                }, array_slice($recentUsers, 0, 3));
            } catch (\Exception $e) {
                $results['recent_users_error'] = $e->getMessage();
            }
            
            return response()->json($results, 200, [], JSON_PRETTY_PRINT);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
                'timestamp' => now()->format('Y-m-d H:i:s')
            ], 500, [], JSON_PRETTY_PRINT);
        }
    }
}
