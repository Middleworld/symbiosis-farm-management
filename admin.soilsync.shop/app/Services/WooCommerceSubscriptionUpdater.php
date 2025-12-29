<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service for updating WooCommerce subscriptions in place
 * instead of creating new subscriptions for every change
 */
class WooCommerceSubscriptionUpdater
{
    /**
     * Update subscription line items (e.g., switching from collection to delivery)
     * 
     * @param int $subscriptionId WooCommerce subscription ID
     * @param array $newLineItems Array of line items to replace existing ones
     * @return bool Success
     */
    public function updateLineItems(int $subscriptionId, array $newLineItems): bool
    {
        try {
            DB::connection('wordpress')->beginTransaction();
            
            // Get existing line items
            $existingItems = DB::connection('wordpress')
                ->table('woocommerce_order_items')
                ->where('order_id', $subscriptionId)
                ->where('order_item_type', 'line_item')
                ->get();
            
            // Delete old line items and their meta
            foreach ($existingItems as $item) {
                DB::connection('wordpress')
                    ->table('woocommerce_order_itemmeta')
                    ->where('order_item_id', $item->order_item_id)
                    ->delete();
                    
                DB::connection('wordpress')
                    ->table('woocommerce_order_items')
                    ->where('order_item_id', $item->order_item_id)
                    ->delete();
            }
            
            // Add new line items
            $newTotal = 0;
            foreach ($newLineItems as $item) {
                $itemId = DB::connection('wordpress')
                    ->table('woocommerce_order_items')
                    ->insertGetId([
                        'order_item_name' => $item['name'],
                        'order_item_type' => 'line_item',
                        'order_id' => $subscriptionId,
                    ]);
                
                // Add line item meta
                $itemMeta = [
                    '_product_id' => $item['product_id'] ?? 0,
                    '_variation_id' => $item['variation_id'] ?? 0,
                    '_qty' => $item['quantity'] ?? 1,
                    '_line_subtotal' => $item['subtotal'] ?? $item['total'] ?? 0,
                    '_line_total' => $item['total'] ?? 0,
                    '_tax_class' => $item['tax_class'] ?? '',
                ];
                
                // Add shipping class if provided
                if (isset($item['shipping_class'])) {
                    $itemMeta['shipping_class'] = $item['shipping_class'];
                }
                
                // Add any custom meta
                if (isset($item['meta'])) {
                    $itemMeta = array_merge($itemMeta, $item['meta']);
                }
                
                foreach ($itemMeta as $key => $value) {
                    DB::connection('wordpress')
                        ->table('woocommerce_order_itemmeta')
                        ->insert([
                            'order_item_id' => $itemId,
                            'meta_key' => $key,
                            'meta_value' => $value,
                        ]);
                }
                
                $newTotal += floatval($item['total'] ?? 0);
            }
            
            // Update subscription total
            DB::connection('wordpress')
                ->table('postmeta')
                ->where('post_id', $subscriptionId)
                ->where('meta_key', '_order_total')
                ->update(['meta_value' => $newTotal]);
            
            DB::connection('wordpress')->commit();
            
            Log::info("Updated WooCommerce subscription line items", [
                'subscription_id' => $subscriptionId,
                'item_count' => count($newLineItems),
                'new_total' => $newTotal
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            DB::connection('wordpress')->rollBack();
            
            Log::error("Failed to update WooCommerce subscription line items", [
                'subscription_id' => $subscriptionId,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    /**
     * Update subscription billing frequency
     * 
     * @param int $subscriptionId WooCommerce subscription ID
     * @param int $interval Billing interval (1, 2, etc.)
     * @param string $period Billing period (week, month, year)
     * @return bool Success
     */
    public function updateBillingFrequency(int $subscriptionId, int $interval, string $period): bool
    {
        try {
            $updates = [
                '_billing_interval' => $interval,
                '_billing_period' => $period,
            ];
            
            foreach ($updates as $key => $value) {
                DB::connection('wordpress')
                    ->table('postmeta')
                    ->updateOrInsert(
                        ['post_id' => $subscriptionId, 'meta_key' => $key],
                        ['meta_value' => $value]
                    );
            }
            
            Log::info("Updated subscription billing frequency", [
                'subscription_id' => $subscriptionId,
                'interval' => $interval,
                'period' => $period
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error("Failed to update billing frequency", [
                'subscription_id' => $subscriptionId,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    /**
     * Switch subscription from collection to delivery (or vice versa)
     * 
     * @param int $subscriptionId WooCommerce subscription ID
     * @param string $newMethod 'collection' or 'delivery'
     * @param float $shippingCost Cost of shipping (0 for collection)
     * @return bool Success
     */
    public function switchDeliveryMethod(int $subscriptionId, string $newMethod, float $shippingCost = 0): bool
    {
        try {
            DB::connection('wordpress')->beginTransaction();
            
            // Get current subscription details
            $sub = \App\Models\WooCommerceOrder::with(['items'])->find($subscriptionId);
            if (!$sub) {
                throw new \Exception("Subscription not found");
            }
            
            // Determine new shipping class
            $newShippingClass = $newMethod === 'collection' ? 
                'Collection From Middle World Farms' : 
                'Delivery';
            
            // Build new line items with updated shipping class
            $newLineItems = [];
            $vegboxTotal = 0;
            
            foreach ($sub->items as $item) {
                $itemName = strtolower($item->order_item_name);
                
                // Skip old collection/delivery line items
                if (strpos($itemName, 'collection') !== false || 
                    strpos($itemName, 'delivery') !== false) {
                    continue;
                }
                
                // Keep vegbox product line items
                $vegboxTotal += floatval($item->getMeta('_line_total'));
                $newLineItems[] = [
                    'name' => $item->order_item_name,
                    'product_id' => $item->getMeta('_product_id'),
                    'variation_id' => $item->getMeta('_variation_id'),
                    'quantity' => $item->getMeta('_qty'),
                    'subtotal' => $item->getMeta('_line_subtotal'),
                    'total' => $item->getMeta('_line_total'),
                    'tax_class' => $item->getMeta('_tax_class'),
                ];
            }
            
            // Add new shipping method line item
            $newLineItems[] = [
                'name' => $newShippingClass,
                'product_id' => 0,
                'quantity' => 1,
                'total' => $shippingCost,
                'subtotal' => $shippingCost,
                'shipping_class' => $newShippingClass,
            ];
            
            // Update line items
            $this->updateLineItems($subscriptionId, $newLineItems);
            
            // Update shipping total
            DB::connection('wordpress')
                ->table('postmeta')
                ->updateOrInsert(
                    ['post_id' => $subscriptionId, 'meta_key' => '_order_shipping'],
                    ['meta_value' => $shippingCost]
                );
            
            // Update order total
            $newTotal = $vegboxTotal + $shippingCost;
            DB::connection('wordpress')
                ->table('postmeta')
                ->updateOrInsert(
                    ['post_id' => $subscriptionId, 'meta_key' => '_order_total'],
                    ['meta_value' => $newTotal]
                );
            
            DB::connection('wordpress')->commit();
            
            Log::info("Switched subscription delivery method", [
                'subscription_id' => $subscriptionId,
                'new_method' => $newMethod,
                'shipping_cost' => $shippingCost,
                'new_total' => $newTotal
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            DB::connection('wordpress')->rollBack();
            
            Log::error("Failed to switch delivery method", [
                'subscription_id' => $subscriptionId,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
}
