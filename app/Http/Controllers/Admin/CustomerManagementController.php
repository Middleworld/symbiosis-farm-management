<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WordPressUser;
use App\Models\WooCommerceOrder;
use App\Services\CustomerSMSService;
use App\Services\WpApiService;
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
        
        try {
            $query = WordPressUser::query();
            
            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('user_login', 'LIKE', "%{$search}%")
                      ->orWhere('user_email', 'LIKE', "%{$search}%")
                      ->orWhere('display_name', 'LIKE', "%{$search}%");
                });
            }
            
            $query->whereHas('meta', function($q) {
                $prefix = config('database.connections.wordpress.prefix', 'D6sPMX_');
                $q->where('meta_key', $prefix . 'capabilities')
                  ->where(function($roleQuery) {
                      $roleQuery->where('meta_value', 'LIKE', '%customer%')
                               ->orWhere('meta_value', 'LIKE', '%subscriber%');
                  });
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
            
            foreach ($users as $user) {
                $orderCount = WooCommerceOrder::where('post_type', 'shop_order')
                    ->whereHas('meta', function($q) use ($user) {
                        $q->where('meta_key', '_customer_user')->where('meta_value', $user->ID);
                    })->count();
                
                $wcData = $user->getWooCommerceData();
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
                    // Check for active subscriptions
                    $subscriptionCount = WooCommerceOrder::where('post_type', 'shop_subscription')
                        ->where('post_status', 'wc-active')
                        ->whereHas('meta', function($q) use ($user) {
                            $q->where('meta_key', '_customer_user')->where('meta_value', $user->ID);
                        })->count();
                    $includeUser = $subscriptionCount > 0;
                }
                if ($filter === 'recent') $includeUser = $user->user_registered >= now()->subDays(30);
                
                if ($includeUser && $orderFilter !== 'any') {
                    if ($orderFilter === 'none') $includeUser = $orderCount == 0;
                    if ($orderFilter === 'some') $includeUser = $orderCount > 0 && $orderCount < 5;
                    if ($orderFilter === 'many') $includeUser = $orderCount >= 5;
                }
                
                if (!$includeUser) continue;
                
                // Check for active subscriptions
                $subscriptionCount = WooCommerceOrder::where('post_type', 'shop_subscription')
                    ->where('post_status', 'wc-active')
                    ->whereHas('meta', function($q) use ($user) {
                        $q->where('meta_key', '_customer_user')->where('meta_value', $user->ID);
                    })->count();
                
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
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid user ID provided'
                ], 400);
            }

            // Check if WordPress user exists
            $wpUser = WordPressUser::find($userId);
            if (!$wpUser) {
                return response()->json([
                    'success' => false,
                    'error' => 'WordPress user not found'
                ], 404);
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
                    'error' => 'Failed to generate switch URL - WordPress API connection failed'
                ], 400);
            }

            \Log::info("Customer page user switch successful", [
                'user_id' => $userId,
                'user_email' => $wpUser->user_email,
                'redirect_to' => $redirectTo
            ]);

            return response()->json([
                'success' => true,
                'switch_url' => $switchUrl,
                'message' => 'Switch URL generated successfully'
            ]);
        } catch (\Exception $e) {
            \Log::error("Customer page user switch failed", [
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
