<?php
/**
 * Custom Shipping Method for MWF Subscriptions
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WC_Shipping_Method')) {
    return;
}

class MWF_Shipping_Method extends WC_Shipping_Method {
    
    public function __construct($instance_id = 0) {
        $this->id = 'mwf_shipping';
        $this->instance_id = absint($instance_id);
        $this->method_title = __('MWF Subscription Shipping', 'mwf-subscriptions');
        $this->method_description = __('Dynamic shipping rates for vegbox subscriptions', 'mwf-subscriptions');
        $this->supports = ['shipping-zones', 'instance-settings'];
        $this->enabled = 'yes';
        
        $this->init();
    }
    
    public function init() {
        $this->init_form_fields();
        $this->init_settings();
        
        add_action('woocommerce_update_options_shipping_' . $this->id, [$this, 'process_admin_options']);
    }
    
    public function init_form_fields() {
        $this->form_fields = [
            'title' => [
                'title' => __('Method Title', 'mwf-subscriptions'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'mwf-subscriptions'),
                'default' => __('Vegbox Delivery', 'mwf-subscriptions'),
                'desc_tip' => true,
            ],
            'collection_available' => [
                'title' => __('Allow Collection', 'mwf-subscriptions'),
                'type' => 'checkbox',
                'label' => __('Enable collection from farm option', 'mwf-subscriptions'),
                'default' => 'yes',
            ],
        ];
    }
    
    public function calculate_shipping($package = []) {
        $shipping_cost = $this->get_shipping_cost_from_cart();
        
        // ALWAYS offer collection option (free)
        $this->add_rate([
            'id' => $this->id . ':collection',
            'label' => __('Collection from Farm (FREE - Saturdays 9am-5pm)', 'mwf-subscriptions'),
            'cost' => 0,
            'meta_data' => [
                'mwf_shipping_type' => 'collection',
            ],
        ]);
        
        // ALWAYS offer delivery option (calculated cost, default £4 minimum)
        $this->add_rate([
            'id' => $this->id . ':delivery',
            'label' => sprintf(__('Delivery (£%s - Thursdays)', 'mwf-subscriptions'), number_format($shipping_cost, 2)),
            'cost' => $shipping_cost,
            'meta_data' => [
                'mwf_shipping_type' => 'delivery',
            ],
        ]);
    }
    
    /**
     * Get shipping cost based on cart items
     */
    private function get_shipping_cost_from_cart() {
        $cart = WC()->cart;
        if (!$cart || $cart->is_empty()) {
            return 0;
        }
        
        foreach ($cart->get_cart() as $cart_item) {
            $product_id = $cart_item['product_id'];
            
            // Check if this is a vegbox subscription
            $is_vegbox = get_post_meta($product_id, '_is_vegbox_subscription', true);
            
            if ($is_vegbox !== 'yes') {
                continue;
            }
            
            // Get variation attributes
            $frequency = '';
            $payment_schedule = '';
            
            if (isset($cart_item['variation'])) {
                foreach ($cart_item['variation'] as $key => $value) {
                    if (strpos($key, 'frequency') !== false) {
                        $frequency = $value;
                    }
                    if (strpos($key, 'payment-schedule') !== false) {
                        $payment_schedule = $value;
                    }
                }
            }
            
            // Calculate shipping cost
            $shipping_costs = [
                'weekly_weekly' => 4,
                'fortnightly_fortnightly' => 4,
                'monthly_weekly' => 19,
                'monthly_fortnightly' => 9,
                'annually_weekly' => 132,
                'annually_fortnightly' => 66,
            ];
            
            $key = $payment_schedule . '_' . $frequency;
            
            if (isset($shipping_costs[$key])) {
                return $shipping_costs[$key];
            }
        }
        
        return 4; // Default delivery cost
    }
}
