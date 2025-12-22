<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * WooCommerce Checkout integration
 * Auto-detects variable subscription products based on Payment option attribute
 */
class MWF_Checkout {
    
    private static $instance = null;
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init_hooks();
    }
    
    private function init_hooks() {
        // Add delivery day selector to checkout
        add_action('woocommerce_after_order_notes', [$this, 'add_delivery_day_field']);
        
        // Validate delivery day
        add_action('woocommerce_checkout_process', [$this, 'validate_delivery_day']);
        
        // Save delivery day to order meta
        add_action('woocommerce_checkout_update_order_meta', [$this, 'save_delivery_day']);
        
        // Create subscription after order completion
        add_action('woocommerce_order_status_completed', [$this, 'create_subscription'], 10, 1);
        add_action('woocommerce_order_status_processing', [$this, 'create_subscription'], 10, 1);
    }
    
    /**
     * Add delivery day selector to checkout
     */
    public function add_delivery_day_field($checkout) {
        // Only show for variable subscription products
        if (!$this->cart_contains_subscription_product()) {
            return;
        }
        
        echo '<div id="mwf_delivery_options">';
        echo '<h3>' . __('Delivery Options', 'mwf-subscriptions') . '</h3>';
        
        woocommerce_form_field('mwf_delivery_day', [
            'type' => 'select',
            'class' => ['form-row-wide'],
            'label' => __('Preferred Delivery Day', 'mwf-subscriptions'),
            'required' => true,
            'options' => [
                '' => __('Select a day', 'mwf-subscriptions'),
                'monday' => __('Monday', 'mwf-subscriptions'),
                'tuesday' => __('Tuesday', 'mwf-subscriptions'),
                'wednesday' => __('Wednesday', 'mwf-subscriptions'),
                'thursday' => __('Thursday', 'mwf-subscriptions'),
                'friday' => __('Friday', 'mwf-subscriptions'),
            ]
        ], $checkout->get_value('mwf_delivery_day'));
        
        echo '</div>';
    }
    
    /**
     * Validate delivery day is selected
     */
    public function validate_delivery_day() {
        if (!$this->cart_contains_subscription_product()) {
            return;
        }
        
        if (empty($_POST['mwf_delivery_day'])) {
            wc_add_notice(__('Please select a delivery day.', 'mwf-subscriptions'), 'error');
        }
    }
    
    /**
     * Save delivery day to order meta
     */
    public function save_delivery_day($order_id) {
        if (!empty($_POST['mwf_delivery_day'])) {
            update_post_meta($order_id, '_mwf_delivery_day', sanitize_text_field($_POST['mwf_delivery_day']));
        }
    }
    
    /**
     * Create subscription via API after order completes
     */
    public function create_subscription($order_id) {
        $order = wc_get_order($order_id);
        
        // Check if order contains subscription products
        if (!$this->order_contains_subscription_product($order)) {
            return;
        }
        
        // Check if subscription already created
        if (get_post_meta($order_id, '_mwf_subscription_created', true)) {
            return; // Already processed
        }
        
        // Get delivery day
        $delivery_day = get_post_meta($order_id, '_mwf_delivery_day', true);
        if (empty($delivery_day)) {
            error_log("MWF Subscriptions: No delivery day set for order {$order_id}");
            $order->add_order_note(__('Failed to create subscription: No delivery day selected.', 'mwf-subscriptions'));
            return;
        }
        
        // Process each subscription product in the order
        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            $variation_id = $item->get_variation_id();
            $product = $item->get_product();
            
            // Check if this is a subscription product
            if (!$this->is_subscription_product($product)) {
                continue;
            }
            
            // Get subscription details from variation
            $subscription_data = $this->get_subscription_data_from_variation($product, $variation_id, $item);
            
            if (!$subscription_data) {
                error_log("MWF Subscriptions: Could not extract subscription data for product {$product_id}");
                continue;
            }
            
            // Get or create plan ID
            $plan_id = $this->get_or_create_plan_id($subscription_data);
            
            // Create subscription via API
            $api = MWF_API_Client::instance();
            $response = $api->create_subscription([
                'wp_user_id' => $order->get_user_id(),
                'wc_order_id' => $order_id,
                'plan_id' => $plan_id,
                'delivery_day' => $delivery_day,
                'delivery_address' => [
                    'address_1' => $order->get_shipping_address_1(),
                    'address_2' => $order->get_shipping_address_2(),
                    'city' => $order->get_shipping_city(),
                    'postcode' => $order->get_shipping_postcode(),
                    'country' => $order->get_shipping_country(),
                ],
                'price' => $item->get_total(),
                'subscription_meta' => [
                    'payment_option' => $subscription_data['payment_option'],
                    'frequency' => $subscription_data['frequency'],
                    'product_name' => $item->get_name(),
                    'shipping_method' => $order->get_shipping_method(),
                ]
            ]);
            
            if ($response['success']) {
                // Store subscription ID in order meta
                update_post_meta($order_id, '_mwf_subscription_id', $response['subscription']['id']);
                update_post_meta($order_id, '_mwf_subscription_created', true);
                $order->add_order_note(
                    sprintf(
                        __('Vegbox subscription #%d created successfully (%s - %s).', 'mwf-subscriptions'),
                        $response['subscription']['id'],
                        $subscription_data['payment_option'],
                        $subscription_data['frequency']
                    )
                );
                
                error_log("MWF Subscriptions: Created subscription {$response['subscription']['id']} for order {$order_id}");
            } else {
                error_log("MWF Subscriptions: Failed to create subscription for order {$order_id}: " . ($response['message'] ?? 'Unknown error'));
                $order->add_order_note(
                    sprintf(
                        __('Failed to create vegbox subscription: %s', 'mwf-subscriptions'),
                        $response['message'] ?? 'Unknown error'
                    )
                );
            }
        }
    }
    
    /**
     * Check if cart contains subscription product (variable product with payment option attribute)
     */
    private function cart_contains_subscription_product() {
        if (!WC()->cart) {
            return false;
        }
        
        foreach (WC()->cart->get_cart() as $cart_item) {
            $product = $cart_item['data'];
            
            if ($this->is_subscription_product($product)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if order contains subscription product
     */
    private function order_contains_subscription_product($order) {
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            
            if ($this->is_subscription_product($product)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if product is a subscription product
     * Detects variable products with 'Payment option' attribute
     */
    private function is_subscription_product($product) {
        if (!$product) {
            return false;
        }
        
        // Get parent product if this is a variation
        $parent_product = $product;
        if ($product->is_type('variation')) {
            $parent_product = wc_get_product($product->get_parent_id());
        }
        
        // Check if it's a variable product
        if (!$parent_product || !$parent_product->is_type('variable')) {
            return false;
        }
        
        // Check for Payment option attribute (pa_payment-option or payment-option)
        $attributes = $parent_product->get_attributes();
        
        return isset($attributes['pa_payment-option']) || isset($attributes['payment-option']);
    }
    
    /**
     * Extract subscription data from variation
     */
    private function get_subscription_data_from_variation($product, $variation_id, $item) {
        $variation = wc_get_product($variation_id);
        
        if (!$variation) {
            return null;
        }
        
        // Get attributes from variation
        $attributes = $variation->get_attributes();
        
        // Extract payment option (Weekly, Monthly, Annual)
        $payment_option = null;
        if (isset($attributes['pa_payment-option'])) {
            $payment_option = $attributes['pa_payment-option'];
        } elseif (isset($attributes['payment-option'])) {
            $payment_option = $attributes['payment-option'];
        }
        
        // Extract frequency (Weekly, Fortnightly)
        $frequency = null;
        if (isset($attributes['pa_frequency'])) {
            $frequency = $attributes['pa_frequency'];
        } elseif (isset($attributes['frequency'])) {
            $frequency = $attributes['frequency'];
        }
        
        if (!$payment_option) {
            return null;
        }
        
        return [
            'payment_option' => strtolower($payment_option),
            'frequency' => strtolower($frequency ?? 'weekly'),
            'variation_id' => $variation_id,
            'product_id' => $product->get_id(),
        ];
    }
    
    /**
     * Get or create plan ID for subscription
     * Maps WooCommerce variation to Laravel VegboxPlan
     */
    private function get_or_create_plan_id($subscription_data) {
        // Option 1: Use variation ID as plan ID (simple mapping)
        // This assumes you've created VegboxPlans in Laravel with IDs matching variation IDs
        
        // Option 2: Use a mapping table stored in WordPress options
        $plan_mapping = get_option('mwf_subscription_plan_mapping', []);
        $key = $subscription_data['payment_option'] . '_' . $subscription_data['frequency'];
        
        if (isset($plan_mapping[$key])) {
            return $plan_mapping[$key];
        }
        
        // Option 3: Default to plan ID 1 (you can customize this logic)
        // For now, return a default plan ID
        // You should set up proper plan mapping in WordPress Admin
        return 1; // Default plan ID
        
        // TODO: Add admin interface to map variations to Laravel plans
    }
}
