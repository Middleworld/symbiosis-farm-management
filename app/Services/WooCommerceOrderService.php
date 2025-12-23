<?php

namespace App\Services;

use App\Models\WooCommerceOrder;
use App\Models\WooCommerceOrderMeta;
use App\Models\WooCommerceOrderItem;
use App\Models\WooCommerceOrderItemMeta;
use App\Models\VegboxSubscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class WooCommerceOrderService
{
    /**
     * Create a renewal order for a subscription
     * 
     * @param VegboxSubscription $subscription
     * @param array $paymentResult Payment result from Stripe
     * @return array ['success' => bool, 'order_id' => int|null, 'message' => string]
     */
    public function createRenewalOrder(VegboxSubscription $subscription, array $paymentResult): array
    {
        try {
            DB::connection('wordpress')->beginTransaction();

            // Get the parent WooCommerce subscription if it exists
            $parentSubscription = null;
            if ($subscription->woo_subscription_id) {
                $parentSubscription = WooCommerceOrder::subscriptions()
                    ->where('ID', $subscription->woo_subscription_id)
                    ->with(['meta', 'items', 'items.meta'])
                    ->first();
            }

            // Create the order post
            $orderId = $this->createOrderPost($subscription, $parentSubscription);

            if (!$orderId) {
                throw new \Exception('Failed to create order post');
            }

            // Add order metadata
            $this->addOrderMetadata($orderId, $subscription, $parentSubscription, $paymentResult);

            // Add order items (line items)
            $this->addOrderItems($orderId, $subscription, $parentSubscription);

            // Update order totals
            $this->updateOrderTotals($orderId, $subscription);

            DB::connection('wordpress')->commit();

            Log::info('Created WooCommerce renewal order', [
                'order_id' => $orderId,
                'subscription_id' => $subscription->id,
                'woo_subscription_id' => $subscription->woo_subscription_id,
                'amount' => $subscription->price,
                'transaction_id' => $paymentResult['transaction_id'] ?? null
            ]);

            return [
                'success' => true,
                'order_id' => $orderId,
                'message' => 'Renewal order created successfully'
            ];

        } catch (\Exception $e) {
            DB::connection('wordpress')->rollBack();

            Log::error('Failed to create WooCommerce renewal order', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'order_id' => null,
                'message' => 'Failed to create renewal order: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Create the order post in WordPress
     */
    private function createOrderPost(VegboxSubscription $subscription, $parentSubscription): ?int
    {
        $now = now();

        // Get customer info
        $customerName = 'Vegbox Subscription';
        $customerId = 0;

        if ($parentSubscription) {
            $customerName = $parentSubscription->getMeta('_billing_first_name') . ' ' . 
                           $parentSubscription->getMeta('_billing_last_name');
            $customerId = $parentSubscription->getMeta('_customer_user') ?: 0;
        }

        // Insert order post
        $orderId = DB::connection('wordpress')->table('posts')->insertGetId([
            'post_author' => 1,
            'post_date' => $now->format('Y-m-d H:i:s'),
            'post_date_gmt' => $now->copy()->timezone('UTC')->format('Y-m-d H:i:s'),
            'post_content' => '',
            'post_title' => sprintf('Order &ndash; %s', $now->format('F j, Y @ h:i A')),
            'post_excerpt' => '',
            'post_status' => 'wc-processing', // Processing status
            'comment_status' => 'open',
            'ping_status' => 'closed',
            'post_password' => 'order_' . wp_generate_password(13, false),
            'post_name' => 'wc_order_' . wp_generate_password(13, false),
            'to_ping' => '',
            'pinged' => '',
            'post_modified' => $now->format('Y-m-d H:i:s'),
            'post_modified_gmt' => $now->copy()->timezone('UTC')->format('Y-m-d H:i:s'),
            'post_content_filtered' => '',
            'post_parent' => 0,
            'guid' => '', // Will be updated with the proper URL
            'menu_order' => 0,
            'post_type' => 'shop_order',
            'post_mime_type' => '',
            'comment_count' => 0,
        ]);

        // Update GUID to proper order URL
        DB::connection('wordpress')->table('posts')
            ->where('ID', $orderId)
            ->update([
                'guid' => sprintf('https://middleworldfarms.org/?post_type=shop_order&#038;p=%d', $orderId)
            ]);

        return $orderId;
    }

    /**
     * Add order metadata
     */
    private function addOrderMetadata(int $orderId, VegboxSubscription $subscription, $parentSubscription, array $paymentResult): void
    {
        $metadata = [
            // Basic order info
            '_order_key' => 'wc_order_' . wp_generate_password(13, false),
            '_order_currency' => 'GBP',
            '_prices_include_tax' => 'no',
            '_customer_user' => $parentSubscription ? ($parentSubscription->getMeta('_customer_user') ?: 0) : 0,
            '_order_total' => number_format($subscription->price, 2, '.', ''),
            '_order_tax' => '0.00',
            '_order_shipping' => '0.00',
            '_order_shipping_tax' => '0.00',
            '_cart_discount' => '0.00',
            '_cart_discount_tax' => '0.00',
            
            // Payment info
            '_payment_method' => 'stripe',
            '_payment_method_title' => 'Credit Card (Stripe)',
            '_transaction_id' => $paymentResult['transaction_id'] ?? '',
            '_stripe_charge_captured' => 'yes',
            '_paid_date' => now()->timestamp,
            
            // Stripe metadata
            '_stripe_customer_id' => $paymentResult['stripe_customer_id'] ?? '',
            '_stripe_payment_intent' => $paymentResult['stripe_payment_intent'] ?? '',
            '_stripe_source_id' => $paymentResult['stripe_source_id'] ?? '',
            
            // Subscription relationship
            '_subscription_renewal' => $subscription->woo_subscription_id ?: '',
            
            // Dates
            '_date_created' => now()->timestamp,
            '_date_completed' => '',
            '_date_paid' => now()->timestamp,
            
            // Billing info
            '_billing_first_name' => $parentSubscription ? $parentSubscription->getMeta('_billing_first_name') : '',
            '_billing_last_name' => $parentSubscription ? $parentSubscription->getMeta('_billing_last_name') : '',
            '_billing_company' => $parentSubscription ? $parentSubscription->getMeta('_billing_company') : '',
            '_billing_address_1' => $parentSubscription ? $parentSubscription->getMeta('_billing_address_1') : '',
            '_billing_address_2' => $parentSubscription ? $parentSubscription->getMeta('_billing_address_2') : '',
            '_billing_city' => $parentSubscription ? $parentSubscription->getMeta('_billing_city') : '',
            '_billing_state' => $parentSubscription ? $parentSubscription->getMeta('_billing_state') : '',
            '_billing_postcode' => $parentSubscription ? $parentSubscription->getMeta('_billing_postcode') : '',
            '_billing_country' => $parentSubscription ? $parentSubscription->getMeta('_billing_country') : 'GB',
            '_billing_email' => $parentSubscription ? $parentSubscription->getMeta('_billing_email') : '',
            '_billing_phone' => $parentSubscription ? $parentSubscription->getMeta('_billing_phone') : '',
            
            // Shipping info (same as billing for vegbox)
            '_shipping_first_name' => $parentSubscription ? $parentSubscription->getMeta('_shipping_first_name') : '',
            '_shipping_last_name' => $parentSubscription ? $parentSubscription->getMeta('_shipping_last_name') : '',
            '_shipping_company' => $parentSubscription ? $parentSubscription->getMeta('_shipping_company') : '',
            '_shipping_address_1' => $parentSubscription ? $parentSubscription->getMeta('_shipping_address_1') : '',
            '_shipping_address_2' => $parentSubscription ? $parentSubscription->getMeta('_shipping_address_2') : '',
            '_shipping_city' => $parentSubscription ? $parentSubscription->getMeta('_shipping_city') : '',
            '_shipping_state' => $parentSubscription ? $parentSubscription->getMeta('_shipping_state') : '',
            '_shipping_postcode' => $parentSubscription ? $parentSubscription->getMeta('_shipping_postcode') : '',
            '_shipping_country' => $parentSubscription ? $parentSubscription->getMeta('_shipping_country') : 'GB',
            
            // Customer notes
            '_customer_note' => '',
            
            // Admin/internal notes
            '_order_notes' => 'Renewal order created by Laravel admin system',
            
            // Created via
            '_created_via' => 'laravel_renewal',
            
            // Version
            '_order_version' => '8.0.0',
        ];

        foreach ($metadata as $key => $value) {
            DB::connection('wordpress')->table('postmeta')->insert([
                'post_id' => $orderId,
                'meta_key' => $key,
                'meta_value' => $value
            ]);
        }
    }

    /**
     * Add order items (line items)
     */
    private function addOrderItems(int $orderId, VegboxSubscription $subscription, $parentSubscription): void
    {
        // Get product name from plan
        $productName = $subscription->plan ? $subscription->plan->name : 'Vegbox Subscription';
        
        // Get line items from parent subscription if available
        $lineItems = [];
        
        if ($parentSubscription && $parentSubscription->items->isNotEmpty()) {
            // Copy items from parent subscription
            foreach ($parentSubscription->items as $item) {
                if ($item->order_item_type === 'line_item') {
                    $lineItems[] = [
                        'product_name' => $item->order_item_name,
                        'product_id' => $item->getMeta('_product_id') ?: 0,
                        'variation_id' => $item->getMeta('_variation_id') ?: 0,
                        'quantity' => $item->getMeta('_qty') ?: 1,
                        'line_total' => $item->getMeta('_line_total') ?: $subscription->price,
                        'line_subtotal' => $item->getMeta('_line_subtotal') ?: $subscription->price,
                    ];
                }
            }
        }
        
        // If no items from parent, create a default one
        if (empty($lineItems)) {
            $lineItems[] = [
                'product_name' => $productName,
                'product_id' => 0,
                'variation_id' => 0,
                'quantity' => 1,
                'line_total' => $subscription->price,
                'line_subtotal' => $subscription->price,
            ];
        }

        // Insert line items
        foreach ($lineItems as $item) {
            $itemId = DB::connection('wordpress')->table('woocommerce_order_items')->insertGetId([
                'order_item_name' => $item['product_name'],
                'order_item_type' => 'line_item',
                'order_id' => $orderId
            ]);

            // Add item metadata
            $itemMeta = [
                '_product_id' => $item['product_id'],
                '_variation_id' => $item['variation_id'],
                '_qty' => $item['quantity'],
                '_line_subtotal' => number_format($item['line_subtotal'], 2, '.', ''),
                '_line_total' => number_format($item['line_total'], 2, '.', ''),
                '_line_subtotal_tax' => '0.00',
                '_line_tax' => '0.00',
                '_line_tax_data' => serialize([
                    'total' => [],
                    'subtotal' => []
                ]),
            ];

            foreach ($itemMeta as $key => $value) {
                DB::connection('wordpress')->table('woocommerce_order_itemmeta')->insert([
                    'order_item_id' => $itemId,
                    'meta_key' => $key,
                    'meta_value' => $value
                ]);
            }
        }
    }

    /**
     * Update order totals
     */
    private function updateOrderTotals(int $orderId, VegboxSubscription $subscription): void
    {
        // Update the order total metadata
        DB::connection('wordpress')->table('postmeta')
            ->where('post_id', $orderId)
            ->where('meta_key', '_order_total')
            ->update([
                'meta_value' => number_format($subscription->price, 2, '.', '')
            ]);
    }
}

/**
 * Helper function to generate WordPress password (simplified)
 */
if (!function_exists('wp_generate_password')) {
    function wp_generate_password($length = 12, $special_chars = true)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        if ($special_chars) {
            $chars .= '!@#$%^&*()';
        }
        
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        
        return $password;
    }
}
