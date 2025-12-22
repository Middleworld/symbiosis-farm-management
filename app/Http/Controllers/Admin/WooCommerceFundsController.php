<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WooCommerceFundsController extends Controller
{
    protected $mwfApiBaseUrl;
    protected $mwfApiKey;

    public function __construct()
    {
        $this->mwfApiBaseUrl = env('MWF_API_BASE_URL', 'https://middleworldfarms.org/wp-json/mwf/v1');
        $this->mwfApiKey = env('MWF_API_KEY', 'Ffsh8yhsuZEGySvLrP0DihCDDwhPwk4h');
    }

    /**
     * Display the WooCommerce Funds dashboard
     */
    public function index()
    {
        return view('admin.funds.index');
    }

    /**
     * Display funds settings page
     */
    public function settings()
    {
        try {
            // TODO: Replace with actual MWF API call when implemented
            // For now, return mock settings for testing

            $settings = [
                'funds_enabled' => true,
                'min_deposit' => 10.00,
                'max_deposit' => 1000.00,
                'deposit_fee' => 0.00,
                'allow_partial_payment' => true,
                'funds_label' => 'Store Credit',
                'funds_description' => 'Use your store credit to pay for future purchases'
            ];

            return view('admin.funds.settings', compact('settings'));

            /*
            $response = Http::withHeaders([
                'X-WC-API-Key' => $this->mwfApiKey,
                'Accept' => 'application/json',
            ])->get("{$this->mwfApiBaseUrl}/funds/settings");

            $settings = [];
            if ($response->successful()) {
                $settings = $response->json()['settings'] ?? [];
            }

            return view('admin.funds.settings', compact('settings'));
            */

        } catch (\Exception $e) {
            Log::error('Failed to load funds settings', ['error' => $e->getMessage()]);
            return view('admin.funds.settings', ['settings' => []]);
        }
    }

    /**
     * Update funds settings
     */
    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'funds_enabled' => 'boolean',
            'min_deposit' => 'nullable|numeric|min:0',
            'max_deposit' => 'nullable|numeric|min:0',
            'deposit_fee' => 'nullable|numeric|min:0|max:100',
            'allow_partial_payment' => 'boolean',
            'funds_label' => 'nullable|string|max:255',
            'funds_description' => 'nullable|string',
        ]);

        try {
            // TODO: Replace with actual MWF API call when implemented
            // For now, just validate and return success for testing

            Log::info('Funds settings updated (mock)', $validated);

            return response()->json([
                'success' => true,
                'message' => 'Settings updated successfully',
                'settings' => $validated
            ]);

            /*
            $response = Http::withHeaders([
                'X-WC-API-Key' => $this->mwfApiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post("{$this->mwfApiBaseUrl}/funds/settings", $validated);

            if ($response->successful()) {
                return response()->json($response->json());
            }

            Log::error('Failed to update funds settings', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return response()->json([
                'error' => 'Failed to update funds settings'
            ], 500);
            */

        } catch (\Exception $e) {
            Log::error('Funds settings update exception', ['error' => $e->getMessage()]);
            return response()->json([
                'error' => 'Connection failed'
            ], 500);
        }
    }

    /**
     * Get customer funds data
     */
    public function getCustomerFunds(Request $request)
    {
        try {
            // 1) Try to get customers from the MWF plugin API (preferred)
            $params = $request->only(['page', 'per_page', 'search']);
            $mwfResponse = Http::withHeaders([
                'X-WC-API-Key' => $this->mwfApiKey,
                'Accept' => 'application/json',
            ])->get("{$this->mwfApiBaseUrl}/funds/customers", $params);

            if ($mwfResponse->successful()) {
                $mwfJson = $mwfResponse->json();
                // If the response looks like the expected structure, return it directly
                if (isset($mwfJson['customers']) || is_array($mwfJson)) {
                    return response()->json($mwfJson);
                }
            }

            // 2) Fallback to the WooCommerce REST API (wc/v3 customers)
            $wcBase = config('services.woocommerce.url') ?? config('services.woocommerce.base_url') ?? env('WOOCOMMERCE_URL');
            $ck = config('services.woocommerce.consumer_key') ?? config('services.woocommerce.key') ?? env('WOOCOMMERCE_CONSUMER_KEY');
            $cs = config('services.woocommerce.consumer_secret') ?? config('services.woocommerce.secret') ?? env('WOOCOMMERCE_CONSUMER_SECRET');

            if (!$wcBase || !$ck || !$cs) {
                Log::warning('WooCommerce credentials not configured for fallback customers endpoint');
                return response()->json(['error' => 'WooCommerce credentials are not configured'], 500);
            }

            $perPage = (int) $request->get('per_page', 50);
            $page = (int) $request->get('page', 1);

            $wcUrl = rtrim($wcBase, '/') . '/wp-json/wc/v3/customers';
            $wcParams = [
                'per_page' => $perPage,
                'page' => $page,
                'consumer_key' => $ck,
                'consumer_secret' => $cs,
            ];

            $customersResp = Http::get($wcUrl, $wcParams);
            if (! $customersResp->successful()) {
                Log::error('Failed to fetch customers from WooCommerce', ['status' => $customersResp->status(), 'body' => $customersResp->body()]);
                return response()->json(['error' => 'Failed to load customers from WooCommerce'], 500);
            }

            $wcCustomers = $customersResp->json();
            $customers = [];

            foreach ($wcCustomers as $c) {
                $balance = 0.0;

                // Try to find a balance in meta_data using more specific heuristics
                if (!empty($c['meta_data']) && is_array($c['meta_data'])) {
                    foreach ($c['meta_data'] as $md) {
                        if (!empty($md['key']) && is_numeric($md['value'])) {
                            $key = strtolower($md['key']);
                            // Look for keys that likely contain funds/balance info
                            if (preg_match('/\b(fund|credit|balance|deposit)\b/i', $key) ||
                                strpos($key, '_wc_deposit') !== false ||
                                strpos($key, 'funds') !== false) {
                                $val = (float) $md['value'];
                                // Sanity check: balances shouldn't be ridiculously high (e.g., > 100,000)
                                if ($val >= 0 && $val < 100000) {
                                    $balance = $val;
                                    break;
                                }
                            }
                        }
                    }
                }

                // Use customer's last modified date as last transaction (fast, no extra API calls)
                $last_transaction = $c['date_modified'] ?? $c['date_created'] ?? null;

                $customers[] = [
                    'id' => $c['id'] ?? null,
                    'name' => trim(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? '')) ?: ($c['name'] ?? ''),
                    'email' => $c['email'] ?? '',
                    'balance' => $balance,
                    'last_transaction' => $last_transaction
                ];
            }

            return response()->json([
                'customers' => $customers,
                'total' => count($customers),
                'page' => $page,
                'per_page' => $perPage
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to load customer funds', ['error' => $e->getMessage()]);
            return response()->json([
                'error' => 'Connection failed'
            ], 500);
        }
    }

    /**
     * Show detailed view of a customer's funds
     */
    public function showCustomer(Request $request, $customerId)
    {
        try {
            // 1) Try to get customer details from MWF plugin API
            $mwfResponse = Http::withHeaders([
                'X-WC-API-Key' => $this->mwfApiKey,
                'Accept' => 'application/json',
            ])->get("{$this->mwfApiBaseUrl}/funds/customers/{$customerId}");

            if ($mwfResponse->successful()) {
                $customer = $mwfResponse->json();
                // Log which API we're using
                Log::info('Using MWF API for customer funds', [
                    'customer_id' => $customerId,
                    'transaction_count' => count($customer['transactions'] ?? [])
                ]);
                return view('admin.funds.show', compact('customer'));
            }

            // Log that we're falling back to WooCommerce
            Log::info('Falling back to WooCommerce API for customer funds', ['customer_id' => $customerId]);

            // 2) Fallback to WooCommerce API
            $wcBase = config('services.woocommerce.url') ?? config('services.woocommerce.base_url') ?? env('WOOCOMMERCE_URL');
            $ck = config('services.woocommerce.consumer_key') ?? config('services.woocommerce.key') ?? env('WOOCOMMERCE_CONSUMER_KEY');
            $cs = config('services.woocommerce.consumer_secret') ?? config('services.woocommerce.secret') ?? env('WOOCOMMERCE_CONSUMER_SECRET');

            if (!$wcBase || !$ck || !$cs) {
                return view('admin.funds.show', ['error' => 'WooCommerce credentials not configured']);
            }

            // Get customer details
            $customerUrl = rtrim($wcBase, '/') . '/wp-json/wc/v3/customers/' . $customerId;
            $customerResp = Http::get($customerUrl, [
                'consumer_key' => $ck,
                'consumer_secret' => $cs,
            ]);

            if (!$customerResp->successful()) {
                return view('admin.funds.show', ['error' => 'Customer not found']);
            }

            $wcCustomer = $customerResp->json();

            // Extract balance from meta_data
            $balance = 0.0;
            if (!empty($wcCustomer['meta_data']) && is_array($wcCustomer['meta_data'])) {
                foreach ($wcCustomer['meta_data'] as $md) {
                    if (!empty($md['key']) && is_numeric($md['value'])) {
                        $key = strtolower($md['key']);
                        if (preg_match('/\b(fund|credit|balance|deposit)\b/i', $key) ||
                            strpos($key, '_wc_deposit') !== false ||
                            strpos($key, 'funds') !== false) {
                            $val = (float) $md['value'];
                            if ($val >= 0 && $val < 100000) {
                                $balance = $val;
                                break;
                            }
                        }
                    }
                }
            }

            // Also check if there are transaction logs in user meta
            $transactionLogs = [];
            if (!empty($wcCustomer['meta_data']) && is_array($wcCustomer['meta_data'])) {
                foreach ($wcCustomer['meta_data'] as $md) {
                    $key = strtolower($md['key'] ?? '');
                    if (strpos($key, 'transaction') !== false ||
                        strpos($key, 'funds_log') !== false ||
                        strpos($key, 'credit_log') !== false) {
                        if (is_array($md['value'])) {
                            $transactionLogs = array_merge($transactionLogs, $md['value']);
                        } elseif (is_string($md['value'])) {
                            // Try to decode if it's JSON
                            $decoded = json_decode($md['value'], true);
                            if (is_array($decoded)) {
                                $transactionLogs = array_merge($transactionLogs, $decoded);
                            }
                        }
                    }
                }
            }

            Log::info('Found transaction logs in user meta', [
                'customer_id' => $customerId,
                'logs_count' => count($transactionLogs)
            ]);

            // Get customer's recent transactions (orders) - fetch multiple pages if needed
            // First, try to get orders by customer ID
            $allOrders = [];
            $page = 1;
            $maxPages = 5; // Increased to 5 pages

            // Try customer-specific orders first
            do {
                $ordersResp = Http::get(rtrim($wcBase, '/') . '/wp-json/wc/v3/orders', [
                    'customer' => $customerId,
                    'per_page' => 50,
                    'orderby' => 'date',
                    'order' => 'desc',
                    'page' => $page,
                    'status' => 'any',
                    'consumer_key' => $ck,
                    'consumer_secret' => $cs,
                ]);

                if ($ordersResp->successful()) {
                    $pageOrders = $ordersResp->json();
                    $allOrders = array_merge($allOrders, $pageOrders);

                    $totalPages = (int) $ordersResp->header('X-WP-TotalPages', 1);
                    $page++;
                } else {
                    break;
                }
            } while ($page <= $maxPages && count($allOrders) < 250);

            // If we got very few orders, also try searching by email to catch more transactions
            if (count($allOrders) < 20 && !empty($wcCustomer['email'])) {
                Log::info('Few orders found by customer ID, trying email search', [
                    'customer_id' => $customerId,
                    'email' => $wcCustomer['email'],
                    'current_count' => count($allOrders)
                ]);

                $emailOrders = [];
                $page = 1;
                do {
                    $emailResp = Http::get(rtrim($wcBase, '/') . '/wp-json/wc/v3/orders', [
                        'search' => $wcCustomer['email'],
                        'per_page' => 50,
                        'orderby' => 'date',
                        'order' => 'desc',
                        'page' => $page,
                        'status' => 'any',
                        'consumer_key' => $ck,
                        'consumer_secret' => $cs,
                    ]);

                    if ($emailResp->successful()) {
                        $pageOrders = $emailResp->json();
                        $emailOrders = array_merge($emailOrders, $pageOrders);

                        $totalPages = (int) $emailResp->header('X-WP-TotalPages', 1);
                        $page++;
                    } else {
                        break;
                    }
                } while ($page <= $maxPages && count($emailOrders) < 250);

                // Merge and deduplicate
                $allOrderIds = array_column($allOrders, 'id');
                foreach ($emailOrders as $order) {
                    if (!in_array($order['id'], $allOrderIds)) {
                        $allOrders[] = $order;
                    }
                }

                Log::info('Added orders from email search', [
                    'email_orders_found' => count($emailOrders),
                    'total_orders_after_merge' => count($allOrders)
                ]);
            }

            $orders = $allOrders;

            $transactions = [];
            if (!empty($orders)) {
                Log::info('Processing WooCommerce orders for customer funds', [
                    'customer_id' => $customerId,
                    'order_count' => count($orders),
                    'date_range' => [
                        'oldest' => !empty($orders) ? end($orders)['date_created'] : null,
                        'newest' => !empty($orders) ? $orders[0]['date_created'] : null
                    ]
                ]);
                foreach ($orders as $order) {
                    $orderTotal = (float) $order['total'];
                    $paymentMethod = strtolower($order['payment_method'] ?? '');
                    $orderStatus = $order['status'] ?? '';

                    // Debug: Log order details to understand the data structure
                    Log::debug('Processing order for transaction classification', [
                        'order_id' => $order['id'],
                        'total' => $orderTotal,
                        'payment_method' => $paymentMethod,
                        'status' => $orderStatus,
                        'line_items_count' => count($order['line_items'] ?? []),
                        'meta_data_count' => count($order['meta_data'] ?? []),
                        'line_items' => array_map(function($item) {
                            return [
                                'name' => $item['name'] ?? '',
                                'sku' => $item['sku'] ?? '',
                                'product_id' => $item['product_id'] ?? ''
                            ];
                        }, $order['line_items'] ?? [])
                    ]);

                    // Determine transaction type based on multiple factors
                    $transactionType = 'withdrawal'; // Default assumption - most orders using store credit are withdrawals
                    $transactionAmount = abs($orderTotal);

                    // Check order meta data for store credit indicators - be more specific
                    $isDeposit = false;
                    $usedStoreCredit = false;

                    if (!empty($order['meta_data']) && is_array($order['meta_data'])) {
                        foreach ($order['meta_data'] as $meta) {
                            $key = strtolower($meta['key'] ?? '');
                            $value = is_string($meta['value'] ?? null) ? strtolower($meta['value']) : '';

                            // Only mark as deposit if very explicitly indicated
                            if (strpos($key, 'store_credit_deposit') !== false ||
                                strpos($key, 'funds_deposit') !== false ||
                                strpos($key, '_wc_deposit_amount') !== false ||
                                (strpos($key, 'deposit_type') !== false && strpos($value, 'store_credit') !== false)) {
                                $isDeposit = true;
                            }

                            // Check for store credit usage
                            if (strpos($key, 'store_credit_used') !== false ||
                                strpos($key, 'funds_used') !== false ||
                                strpos($key, '_store_credit_used') !== false) {
                                $usedStoreCredit = true;
                            }
                        }
                    }

                    // Check line items for store credit products (deposits) - be very specific
                    if (!empty($order['line_items']) && is_array($order['line_items'])) {
                        foreach ($order['line_items'] as $item) {
                            $productName = strtolower($item['name'] ?? '');
                            $sku = strtolower($item['sku'] ?? '');

                            // Only very specific product names/SKUs indicate deposits
                            if (strpos($productName, 'store credit top up') !== false ||
                                strpos($productName, 'store credit deposit') !== false ||
                                strpos($productName, 'credit deposit') !== false ||
                                strpos($sku, 'credit_deposit') !== false ||
                                strpos($sku, 'store_credit_topup') !== false) {
                                $isDeposit = true;
                                break;
                            }
                        }
                    }

                    // Final determination - default to withdrawal
                    if ($isDeposit) {
                        // Only explicitly marked deposits
                        $transactionType = 'deposit';
                    } else {
                        // Everything else is a withdrawal (customer using store credit)
                        $transactionType = 'withdrawal';
                    }

                    // Log the classification decision
                    Log::debug('Order classification result', [
                        'order_id' => $order['id'],
                        'total' => $orderTotal,
                        'is_deposit' => $isDeposit,
                        'used_store_credit' => $usedStoreCredit,
                        'payment_method' => $paymentMethod,
                        'final_type' => $transactionType,
                        'final_amount' => $transactionAmount
                    ]);

                    $transactions[] = [
                        'id' => $order['id'],
                        'date' => $order['date_created'],
                        'type' => $transactionType,
                        'amount' => $transactionAmount,
                        'description' => 'Order #' . $order['id'],
                        'order_id' => $order['id'],
                        'status' => $orderStatus
                    ];
                }
            }

            // Add transactions from user meta logs if they exist
            if (!empty($transactionLogs)) {
                Log::info('Processing transaction logs from user meta', [
                    'logs_count' => count($transactionLogs)
                ]);

                foreach ($transactionLogs as $log) {
                    if (is_array($log) && isset($log['date']) && isset($log['amount'])) {
                        $logType = isset($log['type']) ? $log['type'] : 'deposit';
                        $logAmount = abs((float) $log['amount']);
                        $logDescription = $log['description'] ?? 'Store Credit Transaction';

                        $transactions[] = [
                            'id' => 'meta_' . md5($log['date'] . $logAmount),
                            'date' => $log['date'],
                            'type' => $logType,
                            'amount' => $logAmount,
                            'description' => $logDescription,
                            'order_id' => null,
                            'status' => 'completed'
                        ];
                    }
                }
            }

            // Sort all transactions by date (newest first)
            usort($transactions, function($a, $b) {
                return strtotime($b['date']) - strtotime($a['date']);
            });

            Log::info('Completed processing transactions for customer', [
                'customer_id' => $customerId,
                'transaction_count' => count($transactions)
            ]);

            // Format customer data for view
            $customer = [
                'id' => $wcCustomer['id'],
                'name' => trim(($wcCustomer['first_name'] ?? '') . ' ' . ($wcCustomer['last_name'] ?? '')) ?: ($wcCustomer['name'] ?? ''),
                'email' => $wcCustomer['email'] ?? '',
                'balance' => $balance,
                'date_created' => $wcCustomer['date_created'] ?? null,
                'date_modified' => $wcCustomer['date_modified'] ?? null,
                'billing' => $wcCustomer['billing'] ?? [],
                'shipping' => $wcCustomer['shipping'] ?? [],
                'transactions' => $transactions,
                'total_orders' => count($transactions)
            ];

            return view('admin.funds.show', compact('customer'));

        } catch (\Exception $e) {
            Log::error('Failed to load customer details', ['customer_id' => $customerId, 'error' => $e->getMessage()]);
            return view('admin.funds.show', ['error' => 'Failed to load customer details']);
        }
    }

    /**
     * Get funds transactions
     */
    public function getTransactions(Request $request)
    {
        try {
            // 1) Try to get transactions from the MWF plugin API (preferred)
            $params = $request->only(['page', 'per_page', 'customer_id', 'date_from', 'date_to']);
            $mwfResponse = Http::withHeaders([
                'X-WC-API-Key' => $this->mwfApiKey,
                'Accept' => 'application/json',
            ])->get("{$this->mwfApiBaseUrl}/funds/transactions", $params);

            if ($mwfResponse->successful()) {
                $mwfJson = $mwfResponse->json();
                // If the response looks like the expected structure, return it directly
                if (isset($mwfJson['transactions']) || is_array($mwfJson)) {
                    return response()->json($mwfJson);
                }
            }

            // 2) Fallback to the WooCommerce REST API (wc/v3/orders)
            // Map orders and refunds to transactions
            $wcBase = config('services.woocommerce.url') ?? config('services.woocommerce.base_url') ?? env('WOOCOMMERCE_URL');
            $ck = config('services.woocommerce.consumer_key') ?? config('services.woocommerce.key') ?? env('WOOCOMMERCE_CONSUMER_KEY');
            $cs = config('services.woocommerce.consumer_secret') ?? config('services.woocommerce.secret') ?? env('WOOCOMMERCE_CONSUMER_SECRET');

            if (!$wcBase || !$ck || !$cs) {
                Log::warning('WooCommerce credentials not configured for fallback transactions endpoint');
                return response()->json(['error' => 'WooCommerce credentials are not configured'], 500);
            }

            $perPage = (int) $request->get('per_page', 50);
            $page = (int) $request->get('page', 1);

            $wcUrl = rtrim($wcBase, '/') . '/wp-json/wc/v3/orders';
            $wcParams = [
                'per_page' => $perPage,
                'page' => $page,
                'orderby' => 'date',
                'order' => 'desc',
                'consumer_key' => $ck,
                'consumer_secret' => $cs,
            ];

            // Add filters if provided
            if ($request->has('customer_id')) {
                $wcParams['customer'] = $request->get('customer_id');
            }
            if ($request->has('date_from')) {
                $wcParams['after'] = $request->get('date_from') . 'T00:00:00';
            }
            if ($request->has('date_to')) {
                $wcParams['before'] = $request->get('date_to') . 'T23:59:59';
            }

            $ordersResp = Http::get($wcUrl, $wcParams);
            if (! $ordersResp->successful()) {
                Log::error('Failed to fetch orders from WooCommerce', ['status' => $ordersResp->status(), 'body' => $ordersResp->body()]);
                return response()->json(['error' => 'Failed to load transactions from WooCommerce'], 500);
            }

            $wcOrders = $ordersResp->json();
            $transactions = [];

            foreach ($wcOrders as $order) {
                $customerName = $order['billing']['first_name'] . ' ' . $order['billing']['last_name'];
                $customerName = trim($customerName) ?: 'Unknown Customer';

                // For funds transactions, interpret orders as withdrawals (purchases)
                // Refunds would be deposits, but WooCommerce refunds are separate
                $type = 'withdrawal'; // Assuming purchases reduce funds balance
                $amount = (float) $order['total'];
                $description = 'Purchase: Order #' . $order['id'];

                $transactions[] = [
                    'id' => $order['id'],
                    'date' => $order['date_created'],
                    'customer_name' => $customerName,
                    'customer_id' => $order['customer_id'] ?? null,
                    'type' => $type,
                    'amount' => $amount,
                    'description' => $description,
                    'order_id' => $order['id']
                ];
            }

            return response()->json([
                'transactions' => $transactions,
                'total' => count($transactions),
                'page' => $page,
                'per_page' => $perPage
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to load funds transactions', ['error' => $e->getMessage()]);
            return response()->json([
                'error' => 'Connection failed'
            ], 500);
        }
    }
}
