<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\Customer;
use App\Models\VegboxSubscription;

class POSSubscriptionController extends Controller
{
    /**
     * Vegbox product configurations
     */
    private const VEGBOX_PRODUCTS = [
        'Single Person Vegetable Box' => [
            'price' => 10.00,
            'woo_product_id' => null, // Will be looked up dynamically
        ],
        'Couple\'s Vegetable box' => [
            'price' => 15.00,
            'woo_product_id' => null,
        ],
        'Small Family Vegetable Box' => [
            'price' => 22.00,
            'woo_product_id' => null,
        ],
        'Large Family Vegetable Box' => [
            'price' => 25.00,
            'woo_product_id' => null,
        ],
    ];

    /**
     * Search for existing customers in WordPress/WooCommerce database
     */
    public function searchCustomers(Request $request)
    {
        $query = $request->input('q', '');
        
        if (strlen($query) < 2) {
            return response()->json(['customers' => []]);
        }
        
        try {
            // Use the Customer model's search method which queries WordPress users
            $customers = Customer::search($query);
            
            return response()->json([
                'success' => true,
                'customers' => $customers->toArray()
            ]);
        } catch (\Exception $e) {
            Log::error('Customer search failed', [
                'query' => $query,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Search failed: ' . $e->getMessage(),
                'customers' => []
            ], 500);
        }
    }

    /**
     * Create a new subscription from POS terminal
     */
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'box_type' => 'required|string',
            'box_price' => 'required|numeric|min:0',
            'frequency' => 'required|in:weekly,biweekly',
            'delivery_method' => 'required|in:delivery,collection',
            'start_date' => 'nullable|date|after_or_equal:today',
            'customer.name' => 'required|string|max:255',
            'customer.email' => 'required|email|max:255',
            'customer.phone' => 'required|string|max:50',
            'customer.address' => 'required|string',
            'customer.postcode' => 'required|string|max:20',
            'customer.separate_shipping' => 'nullable|boolean',
            'customer.billing_address' => 'nullable|string',
            'customer.billing_postcode' => 'nullable|string|max:20',
            'customer.shipping_address' => 'nullable|string',
            'customer.shipping_postcode' => 'nullable|string|max:20',
            'payment_intent_id' => 'required|string',  // Payment intent from Stripe
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Start transactions on BOTH databases to ensure atomicity
            DB::beginTransaction();
            DB::connection('wordpress')->beginTransaction();

            $customerData = $request->input('customer');
            $paymentIntentId = $request->input('payment_intent_id');
            $startDate = $request->input('start_date'); // Optional future start date
            
            // Find WordPress user by email
            $wpUser = DB::connection('wordpress')->table('users')
                ->where('user_email', $customerData['email'])
                ->first();
            
            if (!$wpUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer not found in WordPress. Please ensure customer has a WordPress/WooCommerce account.'
                ], 404);
            }
            
            $customerId = $wpUser->ID;
            
            // Get WooCommerce product ID for this vegbox
            $wooProductId = $this->getWooCommerceProductId($request->box_type);
            
            if (!$wooProductId) {
                throw new \Exception("Could not find WooCommerce product for {$request->box_type}");
            }
            
            // Create subscription in local database
            $subscription = $this->createLocalSubscription(
                $customerId,
                $request->box_type,
                $request->box_price,
                $request->frequency,
                $request->delivery_method,
                $wooProductId,
                $paymentIntentId,
                $startDate
            );
            
            // Create WooCommerce subscription
            $wooSubscriptionId = $this->createWooCommerceSubscription(
                $customerId,
                $wooProductId,
                $request->box_price,
                $request->frequency,
                $request->delivery_method,
                $customerData,
                $paymentIntentId,
                $startDate
            );
            
            // Update local subscription with WooCommerce ID (use both fields for compatibility)
            $subscription->woo_subscription_id = $wooSubscriptionId;
            $subscription->woocommerce_subscription_id = $wooSubscriptionId;
            $subscription->save();
            
            // Commit BOTH database transactions
            DB::commit();
            DB::connection('wordpress')->commit();

            Log::info('POS Subscription created', [
                'customer_id' => $customerId,
                'subscription_id' => $subscription->id,
                'woo_subscription_id' => $wooSubscriptionId,
                'box_type' => $request->box_type,
                'frequency' => $request->frequency
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Subscription created successfully',
                'subscription_id' => $subscription->id,
                'customer_id' => $customerId
            ]);

        } catch (\Exception $e) {
            // Rollback BOTH database transactions
            DB::rollBack();
            DB::connection('wordpress')->rollBack();
            
            Log::error('Failed to create POS subscription', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create subscription: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Find existing customer or create new one
     */
    // Method removed - now using WordPress user ID directly

    /**
     * Get WooCommerce product ID for a vegbox type
     */
    private function getWooCommerceProductId(string $boxType)
    {
        // Query WordPress database for product
        $product = DB::connection('wordpress')
            ->table('posts')
            ->join('postmeta', 'posts.ID', '=', 'postmeta.post_id')
            ->where('posts.post_type', 'product')
            ->where('posts.post_status', 'publish')
            ->where('posts.post_title', $boxType)
            ->where('postmeta.meta_key', '_subscription_price')
            ->select('posts.ID')
            ->first();
        
        return $product ? $product->ID : null;
    }

    /**
     * Create subscription record in local database
     */
    private function createLocalSubscription(
        int $customerId,
        string $boxType,
        float $boxPrice,
        string $frequency,
        string $deliveryMethod,
        int $wooProductId,
        string $paymentIntentId,
        ?string $startDate = null
    ) {
        $now = now();
        $interval = $frequency === 'weekly' ? 1 : 2;
        
        // Determine start date - either immediate or scheduled
        $startsAt = $startDate ? \Carbon\Carbon::parse($startDate) : $now;
        
        // Calculate next billing and delivery based on start date
        $nextBillingAt = $startsAt->copy()->addWeeks($interval);
        $nextDeliveryDate = $startDate ? 
            $this->calculateNextDeliveryDate($frequency, $startsAt) : 
            $this->calculateNextDeliveryDate($frequency);
        
        return VegboxSubscription::create([
            // Subscriber info (polymorphic)
            'subscriber_id' => $customerId,
            'subscriber_type' => 'App\\Models\\Customer',
            
            // Base subscription fields
            'plan_id' => null, // No plan for POS subscriptions
            'slug' => 'pos-vegbox-' . time(),
            'name' => json_encode(['en' => $boxType]),
            'description' => json_encode(['en' => "POS Subscription - {$boxType}"]),
            'price' => $boxPrice,
            'currency' => 'GBP',
            'starts_at' => $startsAt,
            'billing_frequency' => $interval,
            'billing_period' => 'week',
            'next_billing_at' => $nextBillingAt,
            
            // Vegbox-specific fields
            'box_type' => $boxType,
            'box_size' => $this->determineBoxSize($boxType),
            'frequency' => $frequency === 'weekly' ? 'week' : '2 weeks',
            'status' => 'active',
            'next_delivery_date' => $nextDeliveryDate,
            'woocommerce_product_id' => $wooProductId,
            'delivery_method' => $deliveryMethod,
            'payment_intent_id' => $paymentIntentId,  // Store payment reference
        ]);
    }

    /**
     * Determine box size category from box type
     */
    private function determineBoxSize(string $boxType): string
    {
        if (str_contains($boxType, 'Single Person')) return 'Single Person';
        if (str_contains($boxType, 'Couple')) return "Couple's";
        if (str_contains($boxType, 'Small Family')) return 'Small Family';
        if (str_contains($boxType, 'Large Family')) return 'Large Family';
        return 'Small Family';
    }

    /**
     * Calculate next delivery date based on frequency
     */
    private function calculateNextDeliveryDate(string $frequency, ?\Carbon\Carbon $fromDate = null): \DateTime
    {
        // Get next delivery day (e.g., next Thursday)
        $deliveryDay = config('vegbox.delivery_day', 'Thursday');
        
        if ($fromDate) {
            // Start from the given date
            $nextDelivery = $fromDate->copy();
            
            // Find next occurrence of delivery day
            if ($nextDelivery->format('l') !== $deliveryDay) {
                $nextDelivery->modify('next ' . $deliveryDay);
            }
            
            return $nextDelivery->toDateTime();
        }
        
        // Immediate start - find next delivery day
        $nextDelivery = new \DateTime('next ' . $deliveryDay);
        
        // If today is the delivery day and it's still early, use today
        if ($nextDelivery->format('l') === $deliveryDay) {
            $now = new \DateTime();
            if ($now->format('H') < 12) { // Before noon
                return $now;
            }
        }
        
        return $nextDelivery;
    }

    /**
     * Create subscription in WooCommerce via WordPress database
     */
    private function createWooCommerceSubscription(
        int $customerId,
        int $productId,
        float $price,
        string $frequency,
        string $deliveryMethod,
        array $customerData,
        string $paymentIntentId,
        ?string $startDate = null
    ): int {
        // customerId is already the WordPress user ID
        $wooCustomerId = $customerId;
        
        // Get user meta for addresses
        $userMeta = DB::connection('wordpress')->table('usermeta')
            ->where('user_id', $customerId)
            ->whereIn('meta_key', ['billing_address_1', 'billing_postcode', 'shipping_address_1', 'shipping_postcode'])
            ->get()
            ->keyBy('meta_key');
        
        // Determine shipping class based on delivery method
        $shippingClass = $deliveryMethod === 'collection' ? 
            'Collection From Middle World Farms' : 
            'delivery';
        
        // Determine addresses
        $useSeparateShipping = !empty($customerData['separate_shipping']);
        $billingAddress = $useSeparateShipping ? 
            ($customerData['billing_address'] ?? $customerData['address']) : 
            $customerData['address'];
        $billingPostcode = $useSeparateShipping ? 
            ($customerData['billing_postcode'] ?? $customerData['postcode']) : 
            $customerData['postcode'];
        $shippingAddress = $useSeparateShipping ? 
            ($customerData['shipping_address'] ?? $customerData['address']) : 
            $customerData['address'];
        $shippingPostcode = $useSeparateShipping ? 
            ($customerData['shipping_postcode'] ?? $customerData['postcode']) : 
            $customerData['postcode'];
        
        // Create subscription order in WooCommerce
        $orderId = DB::connection('wordpress')->table('posts')->insertGetId([
            'post_author' => $wooCustomerId,
            'post_date' => now(),
            'post_date_gmt' => now(),
            'post_content' => '',
            'post_title' => 'Subscription from POS',
            'post_excerpt' => '',
            'post_status' => 'wc-active',
            'comment_status' => 'closed',
            'ping_status' => 'closed',
            'post_password' => '',
            'post_name' => 'subscription-' . time(),
            'to_ping' => '',
            'pinged' => '',
            'post_modified' => now(),
            'post_modified_gmt' => now(),
            'post_content_filtered' => '',
            'post_parent' => 0,
            'guid' => '',
            'menu_order' => 0,
            'post_type' => 'shop_subscription',
            'post_mime_type' => '',
            'comment_count' => 0,
        ]);
        
        // Add subscription meta
        $interval = $frequency === 'weekly' ? 1 : 2;
        $period = 'week';
        
        // Parse customer name from customerData
        $nameParts = explode(' ', $customerData['name']);
        $firstName = $nameParts[0] ?? '';
        $lastName = implode(' ', array_slice($nameParts, 1)) ?: '';
        
        // Calculate next payment date based on start date
        $nextPaymentDate = $startDate ? 
            $this->calculateNextDeliveryDate($frequency, \Carbon\Carbon::parse($startDate))->format('Y-m-d H:i:s') :
            $this->calculateNextDeliveryDate($frequency)->format('Y-m-d H:i:s');
        
        $meta = [
            '_customer_user' => $wooCustomerId,
            '_billing_first_name' => $firstName,
            '_billing_last_name' => $lastName,
            '_billing_email' => $customerData['email'],
            '_billing_phone' => $customerData['phone'],
            '_billing_address_1' => $billingAddress,
            '_billing_postcode' => $billingPostcode,
            '_shipping_first_name' => $firstName,
            '_shipping_last_name' => $lastName,
            '_shipping_address_1' => $shippingAddress,
            '_shipping_postcode' => $shippingPostcode,
            '_order_total' => $price,
            '_order_currency' => 'GBP',
            '_subscription_status' => 'active',
            '_billing_interval' => $interval,
            '_billing_period' => $period,
            '_schedule_next_payment' => $nextPaymentDate,
            '_shipping_class' => $shippingClass,
            '_stripe_payment_intent' => $paymentIntentId,  // Store payment reference
        ];
        
        // Add scheduled start date if provided
        if ($startDate) {
            $meta['_subscription_start_date'] = $startDate;
            $meta['_subscription_scheduled'] = 'yes';
        }
        
        foreach ($meta as $key => $value) {
            DB::connection('wordpress')->table('postmeta')->insert([
                'post_id' => $orderId,
                'meta_key' => $key,
                'meta_value' => $value,
            ]);
        }
        
        // Add line item for the product with shipping class
        $itemId = DB::connection('wordpress')->table('woocommerce_order_items')->insertGetId([
            'order_item_name' => 'Vegbox Subscription',
            'order_item_type' => 'line_item',
            'order_id' => $orderId,
        ]);
        
        // Add line item meta including shipping class
        $itemMeta = [
            '_product_id' => $productId,
            '_variation_id' => 0,
            '_qty' => 1,
            '_tax_class' => '',
            '_line_subtotal' => $price,
            '_line_total' => $price,
            'shipping_class' => $shippingClass,
        ];
        
        foreach ($itemMeta as $key => $value) {
            DB::connection('wordpress')->table('woocommerce_order_itemmeta')->insert([
                'order_item_id' => $itemId,
                'meta_key' => $key,
                'meta_value' => $value,
            ]);
        }
        
        return $orderId;
    }

    /**
     * Get or create WooCommerce customer ID
     */
    private function getOrCreateWooCustomer(Customer $customer): int
    {
        // Check if user exists in WordPress
        $wpUser = DB::connection('wordpress')
            ->table('users')
            ->where('user_email', $customer->email)
            ->first();
        
        if ($wpUser) {
            return $wpUser->ID;
        }
        
        // Create new WordPress user
        $userId = DB::connection('wordpress')->table('users')->insertGetId([
            'user_login' => sanitize_user($customer->email),
            'user_pass' => wp_hash_password(wp_generate_password()),
            'user_nicename' => sanitize_title($customer->name),
            'user_email' => $customer->email,
            'user_url' => '',
            'user_registered' => now(),
            'user_activation_key' => '',
            'user_status' => 0,
            'display_name' => $customer->name,
        ]);
        
        // Add customer role
        DB::connection('wordpress')->table('usermeta')->insert([
            'user_id' => $userId,
            'meta_key' => 'wp_capabilities',
            'meta_value' => serialize(['customer' => true]),
        ]);
        
        return $userId;
    }

    /**
     * Get vegbox plan price based on box type, delivery frequency, and payment plan
     */
    public function getPlanPrice(Request $request)
    {
        $boxType = $request->input('box_type'); // e.g., "Small Family Vegetable Box"
        $frequency = $request->input('frequency'); // "weekly" or "biweekly"
        $paymentPlan = $request->input('payment_plan'); // "weekly", "monthly", or "annually"

        // Validate inputs
        if (!$boxType || !$frequency || !$paymentPlan) {
            return response()->json([
                'success' => false,
                'message' => 'Missing required parameters: box_type, frequency, payment_plan'
            ], 400);
        }

        try {
            // Map frequency to database format
            $deliveryFrequency = $frequency === 'biweekly' ? 'bi-weekly' : 'weekly';
            
            // Map payment plan to invoice period/interval
            $billing = $this->getBillingSchedule($paymentPlan);

            // Query VegboxPlan - search by plan name since multiple box types can have same box_size
            $plan = \App\Models\VegboxPlan::where('is_active', true)
                ->where('delivery_frequency', $deliveryFrequency)
                ->where('invoice_period', $billing['period'])
                ->where('invoice_interval', $billing['interval'])
                ->where(function($query) use ($boxType) {
                    // Search for box type in the plan name (JSON field)
                    // The name is stored as JSON: {"en": "Product Title • Payment • Delivery"}
                    $query->whereRaw("JSON_EXTRACT(name, '$.en') LIKE ?", ["%{$boxType}%"]);
                })
                ->first();

            if (!$plan) {
                return response()->json([
                    'success' => false,
                    'message' => 'No matching vegbox plan found',
                    'debug' => [
                        'box_type' => $boxType,
                        'delivery_frequency' => $deliveryFrequency,
                        'invoice_period' => $billing['period'],
                        'invoice_interval' => $billing['interval'],
                    ]
                ], 404);
            }

            return response()->json([
                'success' => true,
                'price' => (float) $plan->price,
                'plan_id' => $plan->id,
                'plan_name' => $plan->name['en'] ?? 'Unknown',
                'currency' => $plan->currency ?? 'GBP',
                'billing' => [
                    'period' => $plan->invoice_period,
                    'interval' => $plan->invoice_interval,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch vegbox plan price', [
                'box_type' => $boxType,
                'frequency' => $frequency,
                'payment_plan' => $paymentPlan,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch plan price: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Map box type display name to database size value
     */
    private function mapBoxTypeToSize(string $boxType): string
    {
        $normalized = strtolower(trim($boxType));
        
        // Order matters: check more specific patterns first
        return match (true) {
            str_contains($normalized, 'single person') => 'Single Person',
            str_contains($normalized, 'small family') => 'Small Family',
            str_contains($normalized, "couple") => "Couple's",
            str_contains($normalized, 'large family') => 'Large Family',
            default => 'Small Family',
        };
    }

    /**
     * Get billing schedule for payment plan
     */
    private function getBillingSchedule(string $paymentPlan): array
    {
        return match (strtolower($paymentPlan)) {
            'weekly' => ['period' => 7, 'interval' => 'day'],
            'fortnightly' => ['period' => 14, 'interval' => 'day'],
            'monthly' => ['period' => 1, 'interval' => 'month'],
            'quarterly' => ['period' => 3, 'interval' => 'month'],
            'annually', 'annual' => ['period' => 1, 'interval' => 'year'],
            default => ['period' => 1, 'interval' => 'month'],
        };
    }
}

/**
 * Helper functions for WordPress compatibility
 */
if (!function_exists('sanitize_user')) {
    function sanitize_user($username) {
        return preg_replace('/[^a-z0-9_@.-]/i', '', strtolower($username));
    }
}

if (!function_exists('sanitize_title')) {
    function sanitize_title($title) {
        return preg_replace('/[^a-z0-9-]/', '-', strtolower($title));
    }
}

if (!function_exists('wp_hash_password')) {
    function wp_hash_password($password) {
        return password_hash($password, PASSWORD_BCRYPT);
    }
}

if (!function_exists('wp_generate_password')) {
    function wp_generate_password() {
        return bin2hex(random_bytes(16));
    }
}
