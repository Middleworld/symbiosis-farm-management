<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VegboxSubscription;
use App\Models\WordPressUser;
use App\Models\WooCommerceOrder;
use App\Services\WpApiService;
use App\Services\CustomerSMSService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerManagementController extends Controller
{
    protected $wpApi;

    public function __construct(WpApiService $wpApi)
    {
        $this->wpApi = $wpApi;
    }
    public function index(Request $request)
    {
        $page = max(1, intval($request->input('page', 1)));
        $perPage = max(10, min(100, intval($request->input('per_page', 25))));
        $search = trim($request->input('q', ''));
        $filter = $request->input('filter', 'all');
        $orderFilter = $request->input('order_filter', 'any');
        $dateFilter = $request->input('date_filter', 'any');
        
        $debug = ['code_version' => 'direct_db_v1'];
        $recentCustomers = [];
        $total = 0;
        
        // Get basic customer stats for iframe view
        try {
            $customerStats = [
                'total_wordpress_users' => \App\Models\WordPressUser::count(),
                'active_subscriptions' => \App\Models\VegboxSubscription::query()->active()->count(),
                'total_orders' => DB::connection('wordpress')->table('posts')->where('post_type', 'shop_order')->count(),
                'recent_orders' => DB::connection('wordpress')->table('posts')
                    ->where('post_type', 'shop_order')
                    ->where('post_date', '>=', now()->subDays(30))
                    ->count(),
            ];
        } catch (\Exception $e) {
            $customerStats = ['error' => 'Unable to load stats: ' . $e->getMessage()];
        }
        
        // Customer processing
        try {
            // Get subscriber IDs from vegbox subscriptions using the same active criteria as VegboxSubscription model
            $subscriberIds = \App\Models\VegboxSubscription::query()
                ->active()
                ->pluck('subscriber_id')
                ->unique()
                ->toArray();
            
            $debug['subscriber_ids_count'] = count($subscriberIds);
            $debug['subscriber_ids_sample'] = array_slice($subscriberIds, 0, 5);
            
            $query = WordPressUser::query();
            
            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('user_login', 'LIKE', "%{$search}%")
                      ->orWhere('user_email', 'LIKE', "%{$search}%")
                      ->orWhere('display_name', 'LIKE', "%{$search}%");
                });
            }
            
            // Include users who have customer/subscriber roles OR are in the subscriber list
            $query->where(function($mainQuery) use ($subscriberIds) {
                $prefix = config('database.connections.wordpress.prefix', 'D6sPMX_');
                $mainQuery->whereHas('meta', function($q) use ($prefix) {
                    $q->where('meta_key', $prefix . 'capabilities')
                      ->where(function($roleQuery) {
                          $roleQuery->where('meta_value', 'LIKE', '%customer%')
                                   ->orWhere('meta_value', 'LIKE', '%subscriber%');
                      });
                })
                ->orWhereIn('ID', $subscriberIds);
            });
            
            if ($dateFilter !== 'any') {
                switch ($dateFilter) {
                    case 'today': $query->where('user_registered', '>=', now()->startOfDay()); break;
                    case 'week': $query->where('user_registered', '>=', now()->subDays(7)); break;
                    case 'month': $query->where('user_registered', '>=', now()->subDays(30)); break;
                    case 'older': $query->where('user_registered', '<', now()->subDays(30)); break;
                }
            }
            
            $total = $query->count();
            $users = $query->orderBy('user_registered', 'desc')
                          ->skip(($page - 1) * $perPage)
                          ->take($perPage)
                          ->get();
            
            $debug['total_users_found'] = $total;
            $debug['users_retrieved_count'] = $users->count();
            $debug['users_sample'] = $users->take(3)->pluck('ID', 'user_login')->toArray();
            $debug['loop_started'] = true;
            $debug['users_processed'] = 0;
            $debug['users_included'] = 0;
            
            foreach ($users as $user) {
                try {
                    $debug['users_processed']++;
                $orderCount = WooCommerceOrder::where('post_type', 'shop_order')
                    ->whereHas('meta', function($q) use ($user) {
                        $q->where('meta_key', '_customer_user')->where('meta_value', $user->ID);
                    })->count();
                
                $wcData = []; // Initialize empty array since getWooCommerceData() doesn't exist
                
                // Fetch WooCommerce billing data from usermeta
                $prefix = config('database.connections.wordpress.prefix', 'D6sPMX_');
                $billingMeta = DB::connection('wordpress')
                    ->table('usermeta')
                    ->where('user_id', $user->ID)
                    ->whereIn('meta_key', [
                        $prefix . 'billing_first_name',
                        $prefix . 'billing_last_name', 
                        $prefix . 'billing_phone',
                        $prefix . 'billing_address_1',
                        $prefix . 'billing_city',
                        $prefix . 'billing_postcode'
                    ])
                    ->pluck('meta_value', 'meta_key')
                    ->toArray();
                
                // Map to wcData array
                $wcData = [
                    'billing_first_name' => $billingMeta[$prefix . 'billing_first_name'] ?? '',
                    'billing_last_name' => $billingMeta[$prefix . 'billing_last_name'] ?? '',
                    'billing_phone' => $billingMeta[$prefix . 'billing_phone'] ?? '',
                    'billing_address_1' => $billingMeta[$prefix . 'billing_address_1'] ?? '',
                    'billing_city' => $billingMeta[$prefix . 'billing_city'] ?? '',
                    'billing_postcode' => $billingMeta[$prefix . 'billing_postcode'] ?? '',
                ];
                
                $email = $user->user_email;
                
                // Build customer name from billing info or username
                $billingName = trim(($wcData['billing_first_name'] ?? '') . ' ' . ($wcData['billing_last_name'] ?? ''));
                if (!empty($billingName) && $billingName !== ' ') {
                    $customerName = $billingName;
                } elseif (!empty($user->display_name) && $user->display_name !== $user->user_login) {
                    // Use WordPress display name if it's different from login
                    $customerName = $user->display_name;
                } else {
                    // Try to create a readable name from email or username
                    $baseName = $user->user_login;
                    
                    // If username contains @, extract the part before @
                    if (strpos($baseName, '@') !== false) {
                        $baseName = explode('@', $baseName)[0];
                    }
                    
                    // Clean up the name: remove numbers at end, capitalize properly
                    $baseName = preg_replace('/\d+$/', '', $baseName); // Remove trailing numbers
                    $baseName = ucwords(str_replace(['_', '-', '.'], ' ', $baseName)); // Replace separators with spaces and capitalize
                    
                    // If it's too short or just numbers, use original
                    if (strlen($baseName) < 2 || is_numeric($baseName)) {
                        $baseName = $user->user_login;
                        if (strpos($baseName, '@') !== false) {
                            $baseName = explode('@', $baseName)[0];
                        }
                        $baseName = ucfirst($baseName);
                    }
                    
                    $customerName = $baseName;
                }
                
                $includeUser = true;
                if ($filter === 'has_orders') $includeUser = $orderCount > 0;
                if ($filter === 'subscribers') {
                    // Check for active subscriptions in both WooCommerce and native vegbox systems
                    $wcSubscriptionCount = WooCommerceOrder::where('post_type', 'shop_subscription')
                        ->where('post_status', 'wc-active')
                        ->whereHas('meta', function($q) use ($user) {
                            $q->where('meta_key', '_customer_user')->where('meta_value', $user->ID);
                        })->count();
                    
                    // Also check native vegbox subscriptions
                    $vegboxSubscriptionCount = \App\Models\VegboxSubscription::where('subscriber_id', $user->ID)
                        ->whereNull('canceled_at')
                        ->whereNull('ends_at')
                        ->count();
                    
                    $includeUser = ($wcSubscriptionCount > 0) || ($vegboxSubscriptionCount > 0);
                }
                if ($filter === 'recent') $includeUser = $user->user_registered >= now()->subDays(30);
                
                if ($includeUser && $orderFilter !== 'any') {
                    if ($orderFilter === 'none') $includeUser = $orderCount == 0;
                    if ($orderFilter === 'some') $includeUser = $orderCount > 0 && $orderCount < 5;
                    if ($orderFilter === 'many') $includeUser = $orderCount >= 5;
                }
                
                if (!$includeUser) continue;
                
                // Check for active subscriptions (both WooCommerce and native vegbox)
                $wcSubscriptionCount = WooCommerceOrder::where('post_type', 'shop_subscription')
                    ->where('post_status', 'wc-active')
                    ->whereHas('meta', function($q) use ($user) {
                        $q->where('meta_key', '_customer_user')->where('meta_value', $user->ID);
                    })->count();
                
                $vegboxSubscriptionCount = \App\Models\VegboxSubscription::query()
                    ->active()
                    ->where('subscriber_id', $user->ID)
                    ->count();
                
                $subscriptionCount = $wcSubscriptionCount + $vegboxSubscriptionCount;
                
                $lastOrder = WooCommerceOrder::where('post_type', 'shop_order')
                    ->whereHas('meta', function($q) use ($user) {
                        $q->where('meta_key', '_customer_user')->where('meta_value', $user->ID);
                    })->orderBy('post_date', 'desc')->first();
                
                $recentCustomers[] = [
                    'id' => $user->ID,
                    'name' => $customerName,
                    'email' => $user->user_email,
                    'phone' => $wcData['billing_phone'],
                    'subscribed' => $subscriptionCount > 0,
                    'joined' => $user->user_registered->format('Y-m-d H:i:s'),
                    'orders_count' => $orderCount,
                    'last_order' => $lastOrder ? $lastOrder->post_date->format('Y-m-d H:i:s') : null,
                ];
                
                $debug['users_included']++;
                $debug['last_user_added'] = $user->user_login;
                } catch (\Exception $e) {
                    $debug['user_errors'][] = [
                        'user_id' => $user->ID,
                        'user_login' => $user->user_login,
                        'error' => $e->getMessage()
                    ];
                }
            }
        } catch (\Exception $e) {
            $debug['error'] = $e->getMessage();
        }
        
        // Build pagination data
        $totalPages = max(1, ceil($total / $perPage));
        $showingFrom = count($recentCustomers) > 0 ? (($page - 1) * $perPage) + 1 : 0;
        $showingTo = min($page * $perPage, $total);
        
        $pagination = [
            'showing_from' => $showingFrom,
            'showing_to' => $showingTo,
            'total_users' => $total,
            'current_page' => $page,
            'per_page' => $perPage,
            'total_pages' => $totalPages,
            'has_prev' => $page > 1,
            'has_next' => $page < $totalPages,
            'prev_page' => $page > 1 ? $page - 1 : null,
            'next_page' => $page < $totalPages ? $page + 1 : null,
        ];
        return view('admin.customers.index', [
            'recentCustomers' => $recentCustomers,
            'debug' => $debug,
            'filter' => $filter,
            'orderFilter' => $orderFilter,
            'dateFilter' => $dateFilter,
            'search' => $search,
            'perPage' => $perPage,
            'pagination' => $pagination,
        ]);
    }
    
    public function switchToUser(Request $request, $userId)
    {
        try {
            // Validate user ID
            if (!$userId || !is_numeric($userId)) {
                return redirect()->back()->with('error', 'Invalid user ID provided');
            }

            // Check if WordPress user exists
            $wpUser = WordPressUser::find($userId);
            if (!$wpUser) {
                return redirect()->back()->with('error', 'WordPress user not found');
            }

            // Get redirect destination from request, default to My Account page
            $redirectTo = $request->input('redirect_to', '/my-account/');
            
            // Generate WordPress auto-login URL that redirects to My Account
            $switchUrl = $this->wpApi->generateUserSwitchUrl(
                $userId, 
                $redirectTo,
                'customer_management_panel'
            );

            if (!$switchUrl) {
                return redirect()->back()->with('error', 'Failed to generate switch URL - WordPress API connection failed');
            }

            \Log::info("Customer page user switch successful", [
                'user_id' => $userId,
                'user_email' => $wpUser->user_email,
                'redirect_to' => $redirectTo
            ]);

            // Redirect directly to the switch URL instead of returning JSON
            return redirect($switchUrl);
        } catch (\Exception $e) {
            \Log::error("Customer page user switch failed", [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()->with('error', 'Server error: ' . $e->getMessage());
        }
    }
    
    public function details($userId)
    {
        $user = WordPressUser::find($userId);
        if (!$user) return response()->json(['error' => 'User not found'], 404);
        return response()->json($user->getFormattedData());
    }

    /**
     * Send SMS campaign to selected customers
     */
    public function sendSMSCampaign(Request $request)
    {
        $request->validate([
            'customer_ids' => 'required|array|min:1',
            'message_type' => 'required|string|in:welcome_back,special_offer,seasonal,custom',
            'custom_message' => 'nullable|string|max:160'
        ]);

        $customerIds = $request->input('customer_ids');
        $messageType = $request->input('message_type');
        $customMessage = $request->input('custom_message');

        // Get customers with phone numbers
        $customers = WordPressUser::whereIn('ID', $customerIds)
            ->whereHas('meta', function($q) {
                $q->where('meta_key', 'billing_phone')
                  ->whereNotNull('meta_value')
                  ->where('meta_value', '!=', '');
            })
            ->with(['meta' => function($query) {
                $query->whereIn('meta_key', ['billing_phone', 'first_name', 'last_name']);
            }])
            ->get();

        if ($customers->isEmpty()) {
            return response()->json([
                'success' => false,
                'error' => 'No customers found with phone numbers'
            ], 400);
        }

        $smsService = new CustomerSMSService();
        $phoneNumbers = [];
        $customerNames = [];

        foreach ($customers as $customer) {
            $phoneMeta = $customer->meta->where('meta_key', 'billing_phone')->first();
            $firstNameMeta = $customer->meta->where('meta_key', 'first_name')->first();
            $lastNameMeta = $customer->meta->where('meta_key', 'last_name')->first();

            if ($phoneMeta && $phoneMeta->meta_value) {
                $phoneNumbers[] = $phoneMeta->meta_value;
                $firstName = $firstNameMeta ? $firstNameMeta->meta_value : '';
                $lastName = $lastNameMeta ? $lastNameMeta->meta_value : '';
                $customerNames[] = trim($firstName . ' ' . $lastName) ?: $customer->display_name;
            }
        }

        if (empty($phoneNumbers)) {
            return response()->json([
                'success' => false,
                'error' => 'No valid phone numbers found'
            ], 400);
        }

        // Send the campaign
        $message = $messageType === 'custom' && $customMessage ? $customMessage : null;
        $result = $smsService->sendBulkCampaign($phoneNumbers, $message, $customerNames);

        return response()->json([
            'success' => true,
            'message' => "SMS campaign sent to {$result['total_sent']} customers",
            'results' => $result
        ]);
    }

    /**
     * Get SMS campaign statistics
     */
    public function getSMSCampaignStats()
    {
        $smsService = new CustomerSMSService();
        $stats = $smsService->getDeliveryStats();

        return response()->json([
            'success' => true,
            'stats' => $stats
        ]);
    }
}
