<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\VegboxSubscription;
use App\Services\WpApiService;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Pagination\LengthAwarePaginator;
use Exception;

class OrderController extends Controller
{
    protected WpApiService $wpApiService;

    public function __construct(WpApiService $wpApiService)
    {
        $this->wpApiService = $wpApiService;
    }

    /**
     * Display orders dashboard
     */
    public function index(Request $request)
    {
        $status = $request->get('status', 'all');
        $search = $request->get('search', '');
        $perPage = $request->get('per_page', 10);
        $month = $request->get('month', '');
        $type = $request->get('type', '');
        $createdVia = $request->get('created_via', '');
        $customerId = $request->get('customer_id', '');
        $dateRange = $request->get('date_range', '');
        $sort = $request->get('sort', 'date');
        $direction = $request->get('dir', 'desc');
        $product = $request->get('product', '');
        $paymentMethod = $request->get('payment_method', '');
        $minAmount = $request->get('min_amount', '');
        $maxAmount = $request->get('max_amount', '');
        $note = $request->get('note', '');
        $page = $request->get('page', 1);
        
        try {
            // Build query parameters
            $params = [
                'per_page' => $perPage,
                'page' => $page,
                'orderby' => 'date',
                'order' => 'desc',
            ];
            
            if ($status !== 'all') {
                $params['status'] = $status;
            }
            
            if (!empty($search)) {
                $params['search'] = $search;
            }
            
            // Add date filter if month is selected
            if (!empty($month)) {
                // Month format: YYYY-MM
                $startDate = $month . '-01T00:00:00';
                $endDate = date('Y-m-t', strtotime($month . '-01')) . 'T23:59:59';
                
                $params['after'] = $startDate;
                $params['before'] = $endDate;
            }
            
            // Add date range filter (overrides month filter if both are set)
            if (!empty($dateRange)) {
                $now = now();
                
                switch ($dateRange) {
                    case 'today':
                        $params['after'] = $now->startOfDay()->toISOString();
                        $params['before'] = $now->endOfDay()->toISOString();
                        break;
                    case 'yesterday':
                        $yesterday = $now->subDay();
                        $params['after'] = $yesterday->startOfDay()->toISOString();
                        $params['before'] = $yesterday->endOfDay()->toISOString();
                        break;
                    case 'this_week':
                        $params['after'] = $now->startOfWeek()->toISOString();
                        $params['before'] = $now->endOfWeek()->toISOString();
                        break;
                    case 'this_month':
                        $params['after'] = $now->startOfMonth()->toISOString();
                        $params['before'] = $now->endOfMonth()->toISOString();
                        break;
                    case 'last_month':
                        $lastMonth = $now->subMonth();
                        $params['after'] = $lastMonth->startOfMonth()->toISOString();
                        $params['before'] = $lastMonth->endOfMonth()->toISOString();
                        break;
                }
            }
            
            // Add order type filter
            if (!empty($type)) {
                $params['type'] = $type;
            }
            
            // Add sales channel filter
            if (!empty($createdVia)) {
                $params['created_via'] = $createdVia;
            }
            
            // Add customer filter
            if (!empty($customerId)) {
                $params['customer'] = $customerId;
            }
            
            // Get orders from WooCommerce with caching
            $cacheKey = 'orders_' . md5(serialize($params));
            $cacheTtl = 120; // 2 minutes for orders (they change frequently)
            
            $response = cache()->remember($cacheKey, $cacheTtl, function () use ($params) {
                return $this->wpApiService->makeRequest('GET', 'wc/v3/orders', $params);
            });
            
            // Check if request was successful
            if (isset($response['error'])) {
                throw new Exception($response['error']);
            }
            
            if (!isset($response['successful']) || !$response['successful']) {
                throw new Exception('WooCommerce API request failed');
            }
            
            // Extract orders from the response data
            $orders = $response['data'] ?? [];
            
            // Check if orders data is valid
            if (!is_array($orders)) {
                throw new Exception('Invalid order data format received from WooCommerce');
            }
            
            // Check if we should show Laravel orders
            $source = $request->get('source', 'woocommerce');
            $showLaravel = in_array($source, ['laravel', 'pos', 'all']);
            $laravelOrders = [];
            
            if ($showLaravel) {
                try {
                    // Get Laravel orders (last 30 days by default)
                    $laravelQuery = Order::with('items')
                        ->where('created_at', '>=', now()->subDays(30));
                    
                    // Filter by order type
                    if ($source === 'laravel') {
                        $laravelQuery->where('order_number', 'LIKE', 'SUB-%'); // Only subscription renewals
                    } elseif ($source === 'pos') {
                        $laravelQuery->where('order_number', 'LIKE', 'POS-%'); // Only POS orders
                    }
                    // If 'all', don't filter by order type
                    
                    // Apply search filter to Laravel orders
                    if (!empty($search)) {
                        $laravelQuery->where(function($q) use ($search) {
                            $q->where('order_number', 'like', "%{$search}%")
                              ->orWhere('customer_email', 'like', "%{$search}%")
                              ->orWhere('customer_name', 'like', "%{$search}%");
                        });
                    }
                    
                    // Apply status filter to Laravel orders
                    if ($status !== 'all') {
                        $laravelQuery->where('order_status', $status);
                    }
                    
                    $laravelOrders = $laravelQuery->orderBy('created_at', 'desc')
                        ->limit(50)
                        ->get()
                        ->map(function($order) {
                            // Transform to WooCommerce-like format for display
                            return [
                                'id' => 'L-' . $order->id, // Prefix with L- to distinguish
                                'order_key' => 'laravel_' . $order->id,
                                'number' => $order->order_number,
                                'status' => $order->order_status ?? 'completed',
                                'currency' => $order->currency ?? 'GBP',
                                'total' => (string) $order->total_amount,
                                'date_created' => $order->created_at->toISOString(),
                                'billing' => [
                                    'first_name' => explode(' ', $order->customer_name ?? '')[0] ?? '',
                                    'last_name' => explode(' ', $order->customer_name ?? '', 2)[1] ?? '',
                                    'email' => $order->customer_email ?? '',
                                ],
                                'line_items' => $order->items->map(function($item) {
                                    return [
                                        'name' => $item->product_name ?? 'Subscription Renewal',
                                        'quantity' => $item->quantity ?? 1,
                                        'total' => (string) ($item->price ?? 0),
                                    ];
                                })->toArray(),
                                'payment_method' => 'stripe',
                                'payment_method_title' => 'Stripe',
                                'customer_note' => 'Subscription Renewal Order',
                                'meta_data' => [
                                    ['key' => '_order_source', 'value' => 'laravel_subscription'],
                                    ['key' => '_payment_intent_id', 'value' => $order->stripe_payment_intent_id ?? ''],
                                ],
                            ];
                        })
                        ->toArray();
                } catch (Exception $e) {
                    Log::error('Failed to fetch Laravel orders: ' . $e->getMessage());
                    // Continue without Laravel orders
                }
            }
            
            // Merge orders if showing all
            if ($source === 'all' && !empty($laravelOrders)) {
                $orders = array_merge($orders, $laravelOrders);
                // Re-sort by date
                usort($orders, function($a, $b) {
                    return strtotime($b['date_created'] ?? 0) <=> strtotime($a['date_created'] ?? 0);
                });
            } elseif (in_array($source, ['laravel', 'pos'])) {
                $orders = $laravelOrders;
            }
            
            // Apply advanced filters
            if (!empty($product) || !empty($paymentMethod) || !empty($minAmount) || !empty($maxAmount) || !empty($note)) {
                $orders = array_filter($orders, function($order) use ($product, $paymentMethod, $minAmount, $maxAmount, $note) {
                    // Product filter
                    if (!empty($product)) {
                        $productFound = false;
                        if (isset($order['line_items']) && is_array($order['line_items'])) {
                            foreach ($order['line_items'] as $item) {
                                if (stripos($item['name'] ?? '', $product) !== false) {
                                    $productFound = true;
                                    break;
                                }
                            }
                        }
                        if (!$productFound) {
                            return false;
                        }
                    }
                    
                    // Payment method filter
                    if (!empty($paymentMethod)) {
                        if (($order['payment_method'] ?? '') !== $paymentMethod && 
                            ($order['payment_method_title'] ?? '') !== $paymentMethod) {
                            return false;
                        }
                    }
                    
                    // Amount range filter
                    $orderTotal = (float) ($order['total'] ?? 0);
                    if (!empty($minAmount) && $orderTotal < (float) $minAmount) {
                        return false;
                    }
                    if (!empty($maxAmount) && $orderTotal > (float) $maxAmount) {
                        return false;
                    }
                    
                    // Order notes filter
                    if (!empty($note)) {
                        $customerNote = $order['customer_note'] ?? '';
                        if (stripos($customerNote, $note) === false) {
                            return false;
                        }
                    }
                    
                    return true;
                });
            }
            
            // Apply sorting if specified
            if (!empty($sort)) {
                usort($orders, function($a, $b) use ($sort, $direction) {
                    $aValue = null;
                    $bValue = null;
                    
                    switch ($sort) {
                        case 'number':
                            $aValue = (int) ($a['id'] ?? 0);
                            $bValue = (int) ($b['id'] ?? 0);
                            break;
                        case 'date':
                            $aValue = strtotime($a['date_created'] ?? '1970-01-01');
                            $bValue = strtotime($b['date_created'] ?? '1970-01-01');
                            break;
                        case 'total':
                            $aValue = (float) ($a['total'] ?? 0);
                            $bValue = (float) ($b['total'] ?? 0);
                            break;
                        default:
                            $aValue = strtotime($a['date_created'] ?? '1970-01-01');
                            $bValue = strtotime($b['date_created'] ?? '1970-01-01');
                    }
                    
                    if ($direction === 'asc') {
                        return $aValue <=> $bValue;
                    } else {
                        return $bValue <=> $aValue;
                    }
                });
            }
            
            // Get total count from WooCommerce API headers
            $totalOrders = 0;
            if (isset($response['headers']['X-WP-Total'])) {
                $headerValue = $response['headers']['X-WP-Total'];
                if (is_array($headerValue)) {
                    $totalOrders = (int) ($headerValue[0] ?? count($orders));
                } else {
                    $totalOrders = (int) $headerValue;
                }
            } else {
                $totalOrders = count($orders);
            }
            
            // Create Laravel paginator
            $ordersCollection = collect($orders);
            $paginatedOrders = new LengthAwarePaginator(
                $ordersCollection,
                $totalOrders,
                $perPage,
                $page,
                [
                    'path' => $request->url(),
                    'query' => $request->query(),
                ]
            );
            
            // Prefetch next page in background if not already cached
            $nextPage = $page + 1;
            if ($nextPage <= $paginatedOrders->lastPage()) {
                $nextPageParams = $params;
                $nextPageParams['page'] = $nextPage;
                $nextPageCacheKey = 'orders_' . md5(serialize($nextPageParams));
                
                if (!cache()->has($nextPageCacheKey)) {
                    // Trigger async prefetch (don't wait for response)
                    $prefetchUrl = route('admin.orders.prefetch');
                    // We'll add JavaScript to trigger this
                }
            }
            
            // Get order stats
            $stats = $this->getOrderStats();
            
            // Get financial summary only if no restrictive filters are applied (for performance)
            // Status 'all' is not a restrictive filter, so we still show financial summary
            $showFinancialSummary = empty($search) && empty($month) && empty($type) && empty($createdVia) && empty($customerId) && 
                empty($dateRange) && empty($product) && empty($paymentMethod) && empty($minAmount) && 
                empty($maxAmount) && empty($note) && ($status === 'all' || empty($status));
            
            // Get financial summary - fetch real data or return zeros
            $financialSummary = $showFinancialSummary ? $this->getFinancialSummary() : [
                'todayRevenue' => 0,
                'weekRevenue' => 0,
                'monthRevenue' => 0,
                'conversionRate' => 0,
            ];
            
            // Get top products from current orders
            $topProducts = $this->getTopProducts($orders);
            
            // Get customer order counts only for customers in current page (performance optimization)
            $customerOrderCounts = $this->getCustomerOrderCounts($orders);
            $recurringOrdersSummary = $this->getRecurringOrderStats();
            $recurringOrders = $this->getRecurringOrders();
            
            return view('admin.orders.index', compact(
                'paginatedOrders',
                'stats',
                'financialSummary',
                'status',
                'search',
                'perPage',
                'month',
                'type',
                'createdVia',
                'customerId',
                'dateRange',
                'sort',
                'direction',
                'product',
                'paymentMethod',
                'minAmount',
                'maxAmount',
                'note'
                ,'recurringOrders'
                ,'recurringOrdersSummary'
            ))->withHeaders([
                'Cache-Control' => 'private, max-age=60', // Browser cache for 1 minute
                'Vary' => 'Accept-Encoding'
            ]);
            
        } catch (Exception $e) {
            Log::error('Failed to fetch orders', ['error' => $e->getMessage()]);
            
            // Create empty paginator for error case
            $paginatedOrders = new LengthAwarePaginator(
                collect([]),
                0,
                $perPage,
                $page,
                [
                    'path' => $request->url(),
                    'query' => $request->query(),
                ]
            );
            
            return view('admin.orders.index', [
                'paginatedOrders' => $paginatedOrders,
                'status' => $status,
                'search' => $search,
                'stats' => [
                    'total' => 0,
                    'processing' => 0,
                    'completed' => 0,
                    'pending' => 0,
                    'on_hold' => 0,
                    'cancelled' => 0,
                    'refunded' => 0,
                ],
                'financialSummary' => [
                    'todayRevenue' => 0,
                    'weekRevenue' => 0,
                    'monthRevenue' => 0,
                    'conversionRate' => 0,
                ],
                'topProducts' => [],
                'customerOrderCounts' => [],
                'recurringOrders' => [],
                'recurringOrdersSummary' => [
                    'active' => 0,
                    'paused' => 0,
                    'due_today' => 0,
                    'due_this_week' => 0,
                    'cancelled' => 0,
                ],
                'error' => 'Failed to connect to WooCommerce. Please check your API settings. Error: ' . $e->getMessage(),
            ]);
        }
    }

    private function getRecurringOrderStats(): array
    {
        $active = VegboxSubscription::query()->active()->count();
        $paused = VegboxSubscription::query()
            ->whereNotNull('pause_until')
            ->where('pause_until', '>', now())
            ->count();
        $dueToday = VegboxSubscription::query()
            ->active()
            ->whereDate('next_delivery_date', today())
            ->count();
        $dueThisWeek = VegboxSubscription::query()
            ->active()
            ->whereBetween('next_delivery_date', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();
        $cancelled = VegboxSubscription::query()
            ->whereNotNull('canceled_at')
            ->count();

        return [
            'active' => $active,
            'paused' => $paused,
            'due_today' => $dueToday,
            'due_this_week' => $dueThisWeek,
            'cancelled' => $cancelled,
        ];
    }

    private function getRecurringOrders(int $limit = 75): array
    {
        return VegboxSubscription::with(['plan', 'subscriber'])
            ->orderByRaw('COALESCE(next_delivery_date, next_billing_at, starts_at) ASC')
            ->limit($limit)
            ->get()
            ->map(function (VegboxSubscription $subscription) {
                $nextDelivery = $subscription->next_delivery_date;
                $nextBilling = $subscription->next_billing_at;

                return [
                    'id' => $subscription->id,
                    'woo_subscription_id' => $subscription->woo_subscription_id,
                    'plan_name' => optional($subscription->plan)->name ?? 'Vegbox Plan #' . $subscription->plan_id,
                    'frequency' => optional($subscription->plan)->delivery_frequency ?? 'weekly',
                    'customer_name' => $this->formatSubscriberName($subscription),
                    'customer_email' => optional($subscription->subscriber)->email,
                    'delivery_day' => $subscription->delivery_day ? ucfirst($subscription->delivery_day) : null,
                    'delivery_time' => $subscription->delivery_time ? ucfirst($subscription->delivery_time) : null,
                    'status' => $this->resolveSubscriptionStatus($subscription),
                    'status_badge' => $this->resolveSubscriptionStatusBadge($subscription),
                    'pause_until' => $this->formatDate($subscription->pause_until),
                    'next_delivery' => $this->formatDate($nextDelivery),
                    'next_delivery_human' => $nextDelivery ? $nextDelivery->diffForHumans() : null,
                    'next_billing' => $this->formatDateTime($nextBilling),
                    'next_billing_human' => $nextBilling ? $nextBilling->diffForHumans() : null,
                    'subscription_url' => route('admin.vegbox-subscriptions.show', $subscription->id),
                ];
            })
            ->toArray();
    }

    private function formatSubscriberName(VegboxSubscription $subscription): string
    {
        if ($subscription->subscriber && property_exists($subscription->subscriber, 'name')) {
            return $subscription->subscriber->name;
        }

        if (is_array($subscription->name)) {
            return $subscription->name['en'] ?? collect($subscription->name)->first() ?? 'Subscription #' . $subscription->id;
        }

        return $subscription->name ?: 'Subscription #' . $subscription->id;
    }

    private function resolveSubscriptionStatus(VegboxSubscription $subscription): string
    {
        if ($subscription->canceled_at) {
            return 'Cancelled';
        }

        if ($subscription->pause_until && $subscription->pause_until->isFuture()) {
            return 'Paused';
        }

        if ($subscription->cancels_at && $subscription->cancels_at->isFuture()) {
            return 'Pending Cancellation';
        }

        return 'Active';
    }

    private function resolveSubscriptionStatusBadge(VegboxSubscription $subscription): string
    {
        if ($subscription->canceled_at) {
            return 'danger';
        }

        if ($subscription->pause_until && $subscription->pause_until->isFuture()) {
            return 'warning';
        }

        if ($subscription->cancels_at && $subscription->cancels_at->isFuture()) {
            return 'secondary';
        }

        return 'success';
    }

    private function formatDate(?CarbonInterface $date): ?string
    {
        return $date ? $date->timezone(config('app.timezone'))->format('D, j M Y') : null;
    }

    private function formatDateTime(?CarbonInterface $date): ?string
    {
        return $date ? $date->timezone(config('app.timezone'))->format('D, j M Y · H:i') : null;
    }

    /**
     * Show order details
     */
    public function show($id)
    {
        // Check if this is a Laravel order (prefixed with L-)
        if (str_starts_with($id, 'L-')) {
            $laravelId = str_replace('L-', '', $id);
            $order = Order::with('items')->find($laravelId);
            
            if (!$order) {
                return redirect()->route('admin.orders.index')
                    ->with('error', 'Laravel order not found.');
            }
            
            // Return Laravel order view
            return view('admin.orders.show-laravel', compact('order'));
        }
        
        try {
            $response = $this->wpApiService->makeRequest('GET', "wc/v3/orders/{$id}");
            
            // Check if request was successful
            if (isset($response['error']) || !isset($response['successful']) || !$response['successful']) {
                return redirect()->route('admin.orders.index')
                    ->with('error', 'Order not found.');
            }
            
            $order = $response['data'] ?? null;
            
            if (!$order || !is_array($order) || !isset($order['id'])) {
                return redirect()->route('admin.orders.index')
                    ->with('error', 'Order not found.');
            }
            
            // Fetch Stripe payment intent and payout from WordPress database
            $stripeData = null;
            try {
                $stripeData = DB::connection('wordpress')->table('D6sPMX_postmeta')
                    ->where('post_id', $id)
                    ->whereIn('meta_key', ['_stripe_intent_id', '_stripe_charge_id'])
                    ->pluck('meta_value', 'meta_key');
            } catch (\Exception $e) {
                Log::warning('Failed to fetch Stripe data for order', ['order_id' => $id, 'error' => $e->getMessage()]);
            }
            
            return view('admin.orders.show', compact('order', 'stripeData'));
            
        } catch (Exception $e) {
            Log::error('Failed to fetch order', ['order_id' => $id, 'error' => $e->getMessage()]);
            
            return redirect()->route('admin.orders.index')
                ->with('error', 'Failed to load order details.');
        }
    }

    /**
     * Refund a Laravel subscription order
     */
    public function refundLaravel(Request $request, $id)
    {
        try {
            $order = Order::with(['subscription'])->findOrFail($id);
            
            // Check if already refunded
            if ($order->payment_status === 'refunded') {
                return redirect()->back()
                    ->with('error', 'This order has already been refunded.');
            }
            
            // Check if order has a payment intent
            if (!$order->stripe_payment_intent_id) {
                return redirect()->back()
                    ->with('error', 'No payment intent found for this order.');
            }
            
            // Initialize Stripe
            \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
            
            // Process refund
            $refund = \Stripe\Refund::create([
                'payment_intent' => $order->stripe_payment_intent_id,
                'reason' => $request->input('reason', 'requested_by_customer'),
                'metadata' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'refunded_by' => auth()->user()->email ?? 'admin',
                    'refunded_at' => now()->toDateTimeString(),
                ],
            ]);
            
            // Update order status
            $order->payment_status = 'refunded';
            $order->order_status = 'refunded';
            $order->notes = ($order->notes ? $order->notes . "\n\n" : '') . 
                           "Refund processed on " . now()->format('Y-m-d H:i:s') . 
                           " by " . (auth()->user()->name ?? 'Admin') . 
                           ". Stripe refund ID: " . $refund->id;
            $order->save();
            
            return redirect()->back()
                ->with('success', 'Order refunded successfully. Refund ID: ' . $refund->id);
            
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            Log::error('Stripe refund failed', ['order_id' => $id, 'error' => $e->getMessage()]);
            return redirect()->back()
                ->with('error', 'Stripe refund failed: ' . $e->getMessage());
        } catch (Exception $e) {
            Log::error('Failed to refund order', ['order_id' => $id, 'error' => $e->getMessage()]);
            return redirect()->back()
                ->with('error', 'Failed to process refund: ' . $e->getMessage());
        }
    }
    
    /**
     * Duplicate an order
     */
    public function duplicate($id)
    {
        try {
            // Get the original order
            $response = $this->wpApiService->makeRequest('GET', "wc/v3/orders/{$id}");
            
            if (isset($response['error']) || !isset($response['successful']) || !$response['successful']) {
                return redirect()->route('admin.orders.index')
                    ->with('error', 'Original order not found.');
            }
            
            $originalOrder = $response['data'] ?? null;
            
            if (!$originalOrder || !is_array($originalOrder)) {
                return redirect()->route('admin.orders.index')
                    ->with('error', 'Invalid order data.');
            }
            
            // Prepare new order data (remove ID and date fields)
            $newOrderData = $originalOrder;
            unset($newOrderData['id'], $newOrderData['date_created'], $newOrderData['date_modified'], $newOrderData['date_created_gmt'], $newOrderData['date_modified_gmt']);
            
            // Set status to pending for the new order
            $newOrderData['status'] = 'pending';
            
            // Add note about duplication
            $newOrderData['customer_note'] = ($newOrderData['customer_note'] ?? '') . "\n\n[Duplicated from order #{$id}]";
            
            // Create the new order
            $createResponse = $this->wpApiService->makeRequest('POST', 'wc/v3/orders', $newOrderData);
            
            if (isset($createResponse['error']) || !isset($createResponse['successful']) || !$createResponse['successful']) {
                return redirect()->route('admin.orders.index')
                    ->with('error', 'Failed to duplicate order.');
            }
            
            $newOrder = $createResponse['data'] ?? null;
            
            return redirect()->route('admin.orders.show', $newOrder['id'])
                ->with('success', "Order duplicated successfully. New order #{$newOrder['id']} created.");
            
        } catch (Exception $e) {
            Log::error('Failed to duplicate order', ['order_id' => $id, 'error' => $e->getMessage()]);
            
            return redirect()->route('admin.orders.index')
                ->with('error', 'Failed to duplicate order.');
        }
    }

    /**
     * Update order status
     */
    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,processing,on-hold,completed,cancelled,refunded,failed',
        ]);
        
        try {
            $response = $this->wpApiService->makeRequest('PUT', "wc/v3/orders/{$id}", [
                'status' => $validated['status'],
            ]);
            
            if (isset($response['successful']) && $response['successful'] && isset($response['data']['id'])) {
                Log::info('Order status updated', [
                    'order_id' => $id,
                    'status' => $validated['status'],
                ]);
                
                return redirect()->back()->with('success', 'Order status updated successfully!');
            }
            
            return redirect()->back()->with('error', 'Failed to update order status.');
            
        } catch (Exception $e) {
            Log::error('Failed to update order status', [
                'order_id' => $id,
                'error' => $e->getMessage(),
            ]);
            
            return redirect()->back()->with('error', 'Failed to update order status.');
        }
    }

    /**
     * Add order note
     */
    public function addNote(Request $request, $id)
    {
        $validated = $request->validate([
            'note' => 'required|string',
            'customer_note' => 'boolean',
        ]);
        
        try {
            $result = $this->wpApiService->makeRequest('POST', "wc/v3/orders/{$id}/notes", [
                'note' => $validated['note'],
                'customer_note' => $request->has('customer_note'),
            ]);
            
            if (isset($result['id'])) {
                return redirect()->back()->with('success', 'Note added successfully!');
            }
            
            return redirect()->back()->with('error', 'Failed to add note.');
            
        } catch (Exception $e) {
            Log::error('Failed to add order note', [
                'order_id' => $id,
                'error' => $e->getMessage(),
            ]);
            
            return redirect()->back()->with('error', 'Failed to add note.');
        }
    }

    /**
     * Refund order
     */
    public function refund(Request $request, $id)
    {
        $validated = $request->validate([
            'amount' => 'nullable|numeric|min:0',
            'reason' => 'nullable|string',
            'api_refund' => 'boolean',
        ]);
        
        try {
            $refundData = [
                'reason' => $validated['reason'] ?? '',
                'api_refund' => $request->has('api_refund'),
            ];
            
            if (isset($validated['amount'])) {
                $refundData['amount'] = $validated['amount'];
            }
            
            $result = $this->wpApiService->makeRequest('POST', "wc/v3/orders/{$id}/refunds", $refundData);
            
            if (isset($result['id'])) {
                Log::info('Order refunded', [
                    'order_id' => $id,
                    'amount' => $validated['amount'] ?? 'full',
                ]);
                
                return redirect()->back()->with('success', 'Order refunded successfully!');
            }
            
            return redirect()->back()->with('error', 'Failed to process refund.');
            
        } catch (Exception $e) {
            Log::error('Failed to refund order', [
                'order_id' => $id,
                'error' => $e->getMessage(),
            ]);
            
            return redirect()->back()->with('error', 'Failed to process refund: ' . $e->getMessage());
        }
    }

    /**
     * Download invoice PDF
     */
    public function downloadInvoice($id)
    {
        try {
            $wpUrl = rtrim(config('services.woocommerce.base_url'), '/');
            $apiKey = config('services.wordpress.api_key');
            $pdfUrl = "{$wpUrl}/wp-json/mwf/v1/orders/{$id}/invoice";
            
            Log::info('Generating invoice PDF via MWF API', [
                'order_id' => $id,
                'url' => $pdfUrl
            ]);
            
            // Make authenticated request to MWF API
            $response = Http::withHeaders([
                'X-WC-API-Key' => $apiKey,
                'User-Agent' => 'MWF Laravel Admin',
            ])->get($pdfUrl);
            
            Log::info('PDF API Response', [
                'order_id' => $id,
                'status' => $response->status(),
                'successful' => $response->successful(),
                'content_type' => $response->header('Content-Type'),
                'body_length' => strlen($response->body()),
                'body_preview' => substr($response->body(), 0, 100)
            ]);
            
            if ($response->successful()) {
                $contentType = $response->header('Content-Type');
                
                // Check if it's actually a PDF
                if (strpos($contentType, 'application/pdf') !== false) {
                    Log::info('Returning PDF', ['order_id' => $id, 'size' => strlen($response->body())]);
                    
                    $pdfResponse = response($response->body())
                        ->header('Content-Type', 'application/pdf')
                        ->header('Content-Disposition', "inline; filename=invoice-{$id}.pdf");
                    
                    // Remove X-Frame-Options to allow iframe display
                    $pdfResponse->headers->remove('X-Frame-Options');
                    
                    return $pdfResponse;
                } else {
                    Log::error('Response is not a PDF', [
                        'order_id' => $id,
                        'content_type' => $contentType,
                        'body' => substr($response->body(), 0, 500)
                    ]);
                }
            }
            
            Log::error('Failed to fetch invoice PDF', [
                'order_id' => $id,
                'status' => $response->status(),
                'body' => substr($response->body(), 0, 500)
            ]);
            
            return redirect()->back()->with('error', 'Failed to generate invoice. Please try again.');
            
        } catch (Exception $e) {
            Log::error('Failed to generate invoice', [
                'order_id' => $id,
                'error' => $e->getMessage(),
            ]);
            
            return redirect()->back()->with('error', 'Failed to generate invoice.');
        }
    }

    /**
     * Download packing slip PDF
     */
    public function downloadPackingSlip($id)
    {
        try {
            $wpUrl = rtrim(config('services.woocommerce.base_url'), '/');
            $apiKey = config('services.wordpress.api_key');
            $pdfUrl = "{$wpUrl}/wp-json/mwf/v1/orders/{$id}/packing-slip";
            
            Log::info('Generating packing slip PDF via MWF API', [
                'order_id' => $id,
                'url' => $pdfUrl
            ]);
            
            // Make authenticated request to MWF API
            $response = Http::withHeaders([
                'X-WC-API-Key' => $apiKey,
                'User-Agent' => 'MWF Laravel Admin',
            ])->get($pdfUrl);
            
            Log::info('Packing Slip API Response', [
                'order_id' => $id,
                'status' => $response->status(),
                'successful' => $response->successful(),
                'content_type' => $response->header('Content-Type'),
                'body_length' => strlen($response->body()),
                'body_preview' => substr($response->body(), 0, 100)
            ]);
            
            if ($response->successful()) {
                $contentType = $response->header('Content-Type');
                
                // Check if it's actually a PDF
                if (strpos($contentType, 'application/pdf') !== false) {
                    Log::info('Returning Packing Slip PDF', ['order_id' => $id, 'size' => strlen($response->body())]);
                    
                    $pdfResponse = response($response->body())
                        ->header('Content-Type', 'application/pdf')
                        ->header('Content-Disposition', "inline; filename=packing-slip-{$id}.pdf");
                    
                    // Remove X-Frame-Options to allow iframe display
                    $pdfResponse->headers->remove('X-Frame-Options');
                    
                    return $pdfResponse;
                } else {
                    Log::error('Packing slip response is not a PDF', [
                        'order_id' => $id,
                        'content_type' => $contentType,
                        'body' => substr($response->body(), 0, 500)
                    ]);
                }
            }
            
            Log::error('Failed to fetch packing slip PDF', [
                'order_id' => $id,
                'status' => $response->status(),
                'body' => substr($response->body(), 0, 500)
            ]);
            
            return redirect()->back()->with('error', 'Failed to generate packing slip. Please try again.');
            
        } catch (Exception $e) {
            Log::error('Failed to generate packing slip', [
                'order_id' => $id,
                'error' => $e->getMessage(),
            ]);
            
            return redirect()->back()->with('error', 'Failed to generate packing slip.');
        }
    }

    /**
     * Get top products from current orders
     */
    private function getTopProducts($orders)
    {
        try {
            $productStats = [];
            
            foreach ($orders as $order) {
                if (isset($order['line_items']) && is_array($order['line_items'])) {
                    foreach ($order['line_items'] as $item) {
                        $productId = $item['product_id'] ?? $item['id'] ?? 0;
                        $productName = $item['name'] ?? 'Unknown Product';
                        $quantity = (int) ($item['quantity'] ?? 1);
                        $price = (float) ($item['price'] ?? 0);
                        
                        if (!isset($productStats[$productId])) {
                            $productStats[$productId] = [
                                'name' => $productName,
                                'quantity' => 0,
                                'revenue' => 0,
                            ];
                        }
                        
                        $productStats[$productId]['quantity'] += $quantity;
                        $productStats[$productId]['revenue'] += $price * $quantity;
                    }
                }
            }
            
            // Sort by revenue descending and get top 5
            usort($productStats, function($a, $b) {
                return $b['revenue'] <=> $a['revenue'];
            });
            
            return array_slice($productStats, 0, 5);
            
        } catch (Exception $e) {
            Log::error('Failed to get top products', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Get customer order counts for customers in the current orders (optimized)
     */
    private function getCustomerOrderCounts($orders)
    {
        try {
            $customerIds = [];
            
            // Collect unique customer IDs from current orders only
            foreach ($orders as $order) {
                if (isset($order['customer_id']) && $order['customer_id'] && !in_array($order['customer_id'], $customerIds)) {
                    $customerIds[] = $order['customer_id'];
                }
            }
            
            // Don't limit - get counts for all customers on current page
            $counts = [];
            
            // Get order count for each customer (with error handling)
            foreach ($customerIds as $customerId) {
                try {
                    $response = $this->wpApiService->makeRequest('GET', 'wc/v3/orders', [
                        'customer' => $customerId,
                        'per_page' => 1,
                    ]);
                    
                    $count = 0;
                    
                    // Check for the total count in headers
                    if (isset($response['headers']['X-WP-Total'])) {
                        $headerValue = $response['headers']['X-WP-Total'];
                        $count = is_array($headerValue) ? (int) ($headerValue[0] ?? 0) : (int) $headerValue;
                    } elseif (isset($response['headers']['x-wp-total'])) {
                        // Try lowercase
                        $headerValue = $response['headers']['x-wp-total'];
                        $count = is_array($headerValue) ? (int) ($headerValue[0] ?? 0) : (int) $headerValue;
                    } elseif (isset($response['data']) && is_array($response['data'])) {
                        // Fallback: if we got data, count is at least 1
                        $count = count($response['data']);
                    }
                    
                    Log::info('Customer order count fetched', [
                        'customer_id' => $customerId,
                        'count' => $count,
                        'response_keys' => array_keys($response),
                        'header_keys' => isset($response['headers']) ? array_keys($response['headers']) : []
                    ]);
                    
                    $counts[$customerId] = $count;
                } catch (Exception $e) {
                    Log::warning('Failed to get count for customer', [
                        'customer_id' => $customerId,
                        'error' => $e->getMessage()
                    ]);
                    // Skip this customer if API call fails
                    $counts[$customerId] = 0;
                }
            }
            
            return $counts;
            
        } catch (Exception $e) {
            Log::error('Failed to get customer order counts', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Export orders to CSV
     */
    public function export(Request $request)
    {
        $status = $request->get('status', 'all');
        
        try {
            $params = [
                'per_page' => 100,
                'orderby' => 'date',
                'order' => 'desc',
            ];
            
            if ($status !== 'all') {
                $params['status'] = $status;
            }
            
            $orders = $this->wpApiService->makeRequest('GET', 'wc/v3/orders', $params);
            
            if (!is_array($orders)) {
                return redirect()->back()->with('error', 'No orders to export.');
            }
            
            // Create CSV
            $filename = 'orders_' . date('Y-m-d_His') . '.csv';
            $handle = fopen('php://temp', 'w');
            
            // CSV headers
            fputcsv($handle, [
                'Order ID',
                'Date',
                'Status',
                'Customer Name',
                'Customer Email',
                'Total',
                'Payment Method',
                'Items',
            ]);
            
            // CSV data
            foreach ($orders as $order) {
                $items = [];
                foreach ($order['line_items'] ?? [] as $item) {
                    $items[] = $item['name'] . ' x' . $item['quantity'];
                }
                
                fputcsv($handle, [
                    $order['id'],
                    date('Y-m-d H:i', strtotime($order['date_created'])),
                    $order['status'],
                    $order['billing']['first_name'] . ' ' . $order['billing']['last_name'],
                    $order['billing']['email'],
                    $order['total'],
                    $order['payment_method_title'] ?? '',
                    implode(', ', $items),
                ]);
            }
            
            rewind($handle);
            $csv = stream_get_contents($handle);
            fclose($handle);
            
            return response($csv, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
            
        } catch (Exception $e) {
            Log::error('Failed to export orders', ['error' => $e->getMessage()]);
            
            return redirect()->back()->with('error', 'Failed to export orders.');
        }
    }

    /**
     * Bulk update orders
     */
    public function bulkAction(Request $request)
    {
        // Log all incoming request data
        Log::info('Bulk action request received', [
            'all_data' => $request->all(),
            'order_ids' => $request->input('order_ids'),
            'action' => $request->input('action'),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
        ]);
        
        $validated = $request->validate([
            'action' => 'required|in:mark_processing,mark_completed,mark_on_hold,mark_cancelled,delete,pdf_invoices,pdf_packing_slips',
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'required|integer',
        ]);
        
        Log::info('Bulk action validated', ['validated' => $validated]);
        
        try {
            $action = $validated['action'];
            $orderIds = $validated['order_ids'];
            
            // Handle PDF generation actions
            if ($action === 'pdf_invoices' || $action === 'pdf_packing_slips') {
                $templateType = $action === 'pdf_invoices' ? 'invoice' : 'packing-slip';
                $orderIdsString = implode(',', $orderIds);
                
                // Get WordPress URL from config
                $wpUrl = rtrim(config('services.wordpress.url'), '/');
                $pdfUrl = "{$wpUrl}/wp-admin/admin-ajax.php?action=generate_wpo_wcpdf&template_type={$templateType}&order_ids={$orderIdsString}";
                
                // Return redirect to PDF URL which will trigger download
                return redirect($pdfUrl);
            }
            
            // Handle delete action
            if ($action === 'delete') {
                $response = $this->wpApiService->makeRequest('POST', 'wc/v3/orders/batch', [
                    'delete' => $orderIds
                ]);
                
                if (isset($response['successful']) && $response['successful']) {
                    // Clear order cache after deletion
                    $this->clearOrderCache();
                    
                    $count = count($orderIds);
                    return redirect()->route('admin.orders.index')
                        ->with('success', "{$count} order(s) deleted successfully!");
                }
            }
            
            // Handle status change actions
            $statusMap = [
                'mark_processing' => 'processing',
                'mark_completed' => 'completed',
                'mark_on_hold' => 'on-hold',
                'mark_cancelled' => 'cancelled',
            ];
            
            if (isset($statusMap[$action])) {
                $newStatus = $statusMap[$action];
                $updates = [];
                
                foreach ($orderIds as $orderId) {
                    $updates[] = [
                        'id' => $orderId,
                        'status' => $newStatus
                    ];
                }
                
                Log::info('Bulk status update attempt', [
                    'action' => $action,
                    'new_status' => $newStatus,
                    'order_ids' => $orderIds,
                    'updates' => $updates
                ]);
                
                $response = $this->wpApiService->makeRequest('POST', 'wc/v3/orders/batch', [
                    'update' => $updates
                ]);
                
                Log::info('Bulk status update response', [
                    'successful' => $response['successful'] ?? false,
                    'response' => $response
                ]);
                
                if (isset($response['successful']) && $response['successful']) {
                    // Clear order cache after status update
                    $this->clearOrderCache();
                    
                    $count = count($orderIds);
                    $statusLabel = ucfirst(str_replace('-', ' ', $newStatus));
                    return redirect()->route('admin.orders.index')
                        ->with('success', "{$count} order(s) marked as {$statusLabel}!");
                } else {
                    // Log the failure details
                    Log::error('Bulk status update failed', [
                        'response' => $response,
                        'error' => $response['error'] ?? 'Unknown error'
                    ]);
                    
                    return redirect()->route('admin.orders.index')
                        ->with('error', 'Failed to update orders: ' . ($response['error'] ?? 'Unknown error'));
                }
            }
            
            return redirect()->route('admin.orders.index')
                ->with('error', 'Bulk action failed. Please try again.');
            
        } catch (Exception $e) {
            Log::error('Failed to perform bulk action', [
                'action' => $request->action,
                'error' => $e->getMessage(),
            ]);
            
            return redirect()->route('admin.orders.index')
                ->with('error', 'Failed to perform bulk action: ' . $e->getMessage());
        }
    }
    
    /**
     * Search customers for AJAX autocomplete
     */
    public function searchCustomers(Request $request)
    {
        $search = $request->get('search', '');
        
        if (empty($search)) {
            return response()->json(['customers' => []]);
        }
        
        try {
            // Cache customer search results for 5 minutes
            $cacheKey = 'customer_search_' . md5($search);
            
            $customers = cache()->remember($cacheKey, 300, function () use ($search) {
                // Get more customers to filter client-side since WooCommerce search might be limited
                $response = $this->wpApiService->makeRequest('GET', 'wc/v3/customers', [
                    'per_page' => 100,
                    'orderby' => 'registered_date',
                    'order' => 'desc',
                ]);
                
                return $response['data'] ?? [];
            });
            
            if (empty($customers)) {
                Log::warning('No customers returned from WooCommerce API');
                return response()->json(['customers' => []]);
            }
            
            // Log first customer for debugging
            if (!empty($customers)) {
                Log::info('Sample customer data', ['customer' => $customers[0]]);
                
                // Log all customers with "nat" in their data when searching for "nat"
                if ($search === 'nat' || $search === 'Nat') {
                    foreach ($customers as $customer) {
                        $customerJson = json_encode($customer);
                        if (stripos($customerJson, 'nat') !== false) {
                            Log::info('Customer containing "nat"', [
                                'id' => $customer['id'],
                                'email' => $customer['email'] ?? '',
                                'billing_first' => $customer['billing']['first_name'] ?? '',
                                'billing_last' => $customer['billing']['last_name'] ?? ''
                            ]);
                        }
                    }
                }
            }
            
            $searchLower = strtolower($search);
            
            // Filter customers by search term (name or email)
            $filteredCustomers = array_filter($customers, function($customer) use ($searchLower) {
                // Root level fields
                $firstName = strtolower($customer['first_name'] ?? '');
                $lastName = strtolower($customer['last_name'] ?? '');
                $email = strtolower($customer['email'] ?? '');
                $username = strtolower($customer['username'] ?? '');
                
                // Billing fields (often where actual names are stored)
                $billingFirstName = strtolower($customer['billing']['first_name'] ?? '');
                $billingLastName = strtolower($customer['billing']['last_name'] ?? '');
                $billingEmail = strtolower($customer['billing']['email'] ?? '');
                
                // Shipping fields
                $shippingFirstName = strtolower($customer['shipping']['first_name'] ?? '');
                $shippingLastName = strtolower($customer['shipping']['last_name'] ?? '');
                
                // Combine names
                $fullName = strtolower(trim($firstName . ' ' . $lastName));
                $billingFullName = strtolower(trim($billingFirstName . ' ' . $billingLastName));
                $shippingFullName = strtolower(trim($shippingFirstName . ' ' . $shippingLastName));
                
                $matches = str_contains($firstName, $searchLower) ||
                       str_contains($lastName, $searchLower) ||
                       str_contains($fullName, $searchLower) ||
                       str_contains($billingFirstName, $searchLower) ||
                       str_contains($billingLastName, $searchLower) ||
                       str_contains($billingFullName, $searchLower) ||
                       str_contains($shippingFirstName, $searchLower) ||
                       str_contains($shippingLastName, $searchLower) ||
                       str_contains($shippingFullName, $searchLower) ||
                       str_contains($email, $searchLower) ||
                       str_contains($billingEmail, $searchLower) ||
                       str_contains($username, $searchLower);
                
                // Log matches for debugging with "nat"
                if ($searchLower === 'nat' && $matches) {
                    Log::info('Customer matched', [
                        'id' => $customer['id'],
                        'billing_first' => $billingFirstName,
                        'billing_last' => $billingLastName,
                        'email' => $email
                    ]);
                }
                
                return $matches;
            });
            
            Log::info('Filtered customers', [
                'total' => count($customers),
                'filtered' => count($filteredCustomers),
                'search_term' => $searchLower
            ]);
            
            // Limit to top 20 results
            $filteredCustomers = array_slice($filteredCustomers, 0, 20);
            
            // Format customer data for dropdown
            $formattedCustomers = array_map(function($customer) {
                // Prefer billing name if root name is empty
                $firstName = !empty($customer['first_name']) ? $customer['first_name'] : ($customer['billing']['first_name'] ?? '');
                $lastName = !empty($customer['last_name']) ? $customer['last_name'] : ($customer['billing']['last_name'] ?? '');
                
                // Calculate lifetime value
                $lifetimeValue = 0;
                try {
                    $ordersResponse = $this->wpApiService->makeRequest('GET', 'wc/v3/orders', [
                        'customer' => $customer['id'],
                        'status' => 'completed',
                        'per_page' => 100,
                    ]);
                    
                    if (isset($ordersResponse['data']) && is_array($ordersResponse['data'])) {
                        foreach ($ordersResponse['data'] as $order) {
                            $lifetimeValue += (float) ($order['total'] ?? 0);
                        }
                    }
                } catch (Exception $e) {
                    // Log error but continue
                    Log::warning('Failed to get lifetime value for customer', [
                        'customer_id' => $customer['id'],
                        'error' => $e->getMessage()
                    ]);
                }
                
                return [
                    'id' => $customer['id'],
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $customer['email'] ?? '',
                    'username' => $customer['username'] ?? '',
                    'lifetime_value' => $lifetimeValue,
                ];
            }, $filteredCustomers);
            
            return response()->json(['customers' => array_values($formattedCustomers)]);
            
        } catch (Exception $e) {
            Log::error('Failed to search customers', ['error' => $e->getMessage()]);
            return response()->json(['customers' => []]);
        }
    }

    /**
     * Prefetch orders in background for better performance
     */
    public function prefetchOrders()
    {
        try {
            // Prefetch first few pages of orders
            $pagesToPrefetch = [1, 2, 3]; // Prefetch first 3 pages
            
            foreach ($pagesToPrefetch as $page) {
                $params = [
                    'per_page' => 10,
                    'page' => $page,
                    'orderby' => 'date',
                    'order' => 'desc',
                ];
                
                $cacheKey = 'orders_' . md5(serialize($params));
                $cacheTtl = 120; // 2 minutes
                
                // Only prefetch if not already cached
                if (!cache()->has($cacheKey)) {
                    $response = $this->wpApiService->makeRequest('GET', 'wc/v3/orders', $params);
                    
                    if (isset($response['successful']) && $response['successful']) {
                        cache([$cacheKey => $response], $cacheTtl);
                        Log::info("Prefetched orders page {$page}");
                    }
                }
            }
            
            return response()->json(['success' => true, 'message' => 'Orders prefetched']);
        } catch (Exception $e) {
            Log::error('Failed to prefetch orders', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Get order statistics with caching
     */
    private function getOrderStats()
    {
        return cache()->remember('order_stats', 600, function () { // Cache for 10 minutes
            try {
                $response = $this->wpApiService->makeRequest('GET', 'wc/v3/reports/orders/totals', []);
                
                if (isset($response['data']) && is_array($response['data'])) {
                    $stats = [];
                    $total = 0;
                    
                    foreach ($response['data'] as $stat) {
                        $status = $stat['slug'] ?? 'unknown';
                        $count = $stat['total'] ?? 0;
                        $stats[$status] = $count;
                        $total += $count; // Sum all statuses for total
                    }
                    
                    // Ensure we have a total field
                    $stats['total'] = $total;
                    
                    return $stats;
                }
                
                // Fallback: get total from orders endpoint
                try {
                    $totalResponse = $this->wpApiService->makeRequest('GET', 'wc/v3/orders', [
                        'per_page' => 1,
                    ]);
                    
                    $total = 0;
                    if (isset($totalResponse['headers']['X-WP-Total'])) {
                        $headerValue = $totalResponse['headers']['X-WP-Total'];
                        $total = is_array($headerValue) ? (int) ($headerValue[0] ?? 0) : (int) $headerValue;
                    }
                } catch (Exception $e) {
                    $total = 0;
                }
                
                return [
                    'total' => $total,
                    'pending' => 0,
                    'processing' => 0,
                    'completed' => 0,
                    'cancelled' => 0,
                    'refunded' => 0,
                ];
            } catch (Exception $e) {
                Log::warning('Failed to get order stats', ['error' => $e->getMessage()]);
                
                // Last resort fallback
                try {
                    $totalResponse = $this->wpApiService->makeRequest('GET', 'wc/v3/orders', [
                        'per_page' => 1,
                    ]);
                    
                    $total = 0;
                    if (isset($totalResponse['headers']['X-WP-Total'])) {
                        $headerValue = $totalResponse['headers']['X-WP-Total'];
                        $total = is_array($headerValue) ? (int) ($headerValue[0] ?? 0) : (int) $headerValue;
                    }
                    
                    return [
                        'total' => $total,
                        'pending' => 0,
                        'processing' => 0,
                        'completed' => 0,
                        'cancelled' => 0,
                        'refunded' => 0,
                    ];
                } catch (Exception $fallbackException) {
                    Log::error('Failed to get fallback order total', ['error' => $fallbackException->getMessage()]);
                }
                
                return [
                    'total' => 0,
                    'pending' => 0,
                    'processing' => 0,
                    'completed' => 0,
                    'cancelled' => 0,
                    'refunded' => 0,
                ];
            }
        });
    }

    /**
     * Get financial summary with caching
     */
    private function getFinancialSummary()
    {
        return cache()->remember('financial_summary', 300, function () { // Cache for 5 minutes
            try {
                $now = now();
                
                // Get today's revenue - all completed orders TODAY
                $todayRevenue = $this->calculatePeriodRevenue(
                    $now->copy()->startOfDay(),
                    $now->copy()->endOfDay()
                );
                
                // Get this week's revenue - calculate based on last 7 days instead of week boundaries
                $weekRevenue = $this->calculatePeriodRevenue(
                    $now->copy()->subDays(7)->startOfDay(),
                    $now->copy()->endOfDay()
                );
                
                // Get this month's revenue - last 30 days
                $monthRevenue = $this->calculatePeriodRevenue(
                    $now->copy()->subDays(30)->startOfDay(),
                    $now->copy()->endOfDay()
                );
                
                // Calculate conversion rate (orders this month vs total orders)
                $conversionRate = $this->calculateConversionRate();
                
                Log::info('Financial summary calculated', [
                    'today' => $todayRevenue,
                    'week' => $weekRevenue,
                    'month' => $monthRevenue,
                    'conversion' => $conversionRate,
                    'calculation_date' => $now->toDateTimeString()
                ]);
                
                return [
                    'todayRevenue' => $todayRevenue,
                    'weekRevenue' => $weekRevenue,
                    'monthRevenue' => $monthRevenue,
                    'conversionRate' => $conversionRate,
                ];
            } catch (Exception $e) {
                Log::error('Failed to get financial summary', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                return [
                    'todayRevenue' => 0,
                    'weekRevenue' => 0,
                    'monthRevenue' => 0,
                    'conversionRate' => 0,
                ];
            }
        });
    }

    /**
     * Calculate revenue for a specific date period with pagination
     */
    private function calculatePeriodRevenue($startDate, $endDate): float
    {
        $revenue = 0;
        $page = 1;
        $perPage = 100;
        $hasMore = true;
        $totalOrders = 0;
        
        Log::info('Calculating period revenue', [
            'start' => $startDate->toISOString(),
            'end' => $endDate->toISOString()
        ]);
        
        while ($hasMore) {
            $response = $this->wpApiService->makeRequest('GET', 'wc/v3/orders', [
                'status' => 'completed',
                'after' => $startDate->toISOString(),
                'before' => $endDate->toISOString(),
                'per_page' => $perPage,
                'page' => $page,
            ]);
            
            Log::info('Revenue API response', [
                'page' => $page,
                'has_data' => isset($response['data']),
                'is_array' => isset($response['data']) && is_array($response['data']),
                'count' => isset($response['data']) && is_array($response['data']) ? count($response['data']) : 0,
                'total_header' => $response['headers']['X-WP-Total'][0] ?? 'missing',
                'sample_order' => isset($response['data'][0]) ? [
                    'id' => $response['data'][0]['id'] ?? null,
                    'total' => $response['data'][0]['total'] ?? null,
                    'status' => $response['data'][0]['status'] ?? null,
                ] : null
            ]);
            
            if (isset($response['data']) && is_array($response['data']) && !empty($response['data'])) {
                foreach ($response['data'] as $order) {
                    $orderTotal = (float) ($order['total'] ?? 0);
                    $revenue += $orderTotal;
                    $totalOrders++;
                }
                
                // Check if there are more pages
                $totalPages = (int) ($response['headers']['X-WP-TotalPages'][0] ?? 1);
                $hasMore = $page < $totalPages;
                $page++;
            } else {
                $hasMore = false;
            }
        }
        
        Log::info('Period revenue calculated', [
            'revenue' => $revenue,
            'orders_processed' => $totalOrders,
            'pages' => $page - 1
        ]);
        
        return $revenue;
    }

    /**
     * Calculate conversion rate (completed orders vs all orders this month)
     */
    private function calculateConversionRate(): float
    {
        try {
            $now = now();
            
            // Get all orders this month
            $allOrdersResponse = $this->wpApiService->makeRequest('GET', 'wc/v3/orders', [
                'after' => $now->copy()->startOfMonth()->toISOString(),
                'before' => $now->copy()->endOfMonth()->toISOString(),
                'per_page' => 1,
            ]);
            
            $totalOrders = (int) ($allOrdersResponse['headers']['X-WP-Total'][0] ?? 0);
            
            // Get completed orders this month
            $completedOrdersResponse = $this->wpApiService->makeRequest('GET', 'wc/v3/orders', [
                'status' => 'completed',
                'after' => $now->copy()->startOfMonth()->toISOString(),
                'before' => $now->copy()->endOfMonth()->toISOString(),
                'per_page' => 1,
            ]);
            
            $completedOrders = (int) ($completedOrdersResponse['headers']['X-WP-Total'][0] ?? 0);
            
            if ($totalOrders > 0) {
                return round(($completedOrders / $totalOrders) * 100, 1);
            }
            
            return 0.0;
        } catch (Exception $e) {
            Log::warning('Failed to calculate conversion rate', ['error' => $e->getMessage()]);
            return 0.0;
        }
    }

    /**
     * Clear all order-related cache
     */
    private function clearOrderCache()
    {
        // Clear order stats cache
        \Cache::forget('order_stats');
        
        // Clear financial summary cache
        \Cache::forget('financial_summary');
        
        // Since order cache keys are hashed, we need to flush all caches starting with 'orders_'
        // This is a limitation of the current caching approach
        // For now, we'll clear the application cache entirely for order-related operations
        // In production, consider using cache tags for better granularity
        
        try {
            \Artisan::call('cache:clear');
        } catch (\Exception $e) {
            Log::warning('Failed to clear cache', ['error' => $e->getMessage()]);
        }
    }
}
