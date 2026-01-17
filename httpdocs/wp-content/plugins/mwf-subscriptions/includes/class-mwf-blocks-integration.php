<?php
/**
 * WooCommerce Blocks Integration for MWF Shipping
 * 
 * Handles shipping options for WooCommerce Checkout Blocks
 */

if (!defined('ABSPATH')) {
    exit;
}

class MWF_Blocks_Integration {
    
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
        // Register custom shipping method for blocks
        add_filter('woocommerce_shipping_methods', [$this, 'register_shipping_method']);
        
        // Add shipping options to Store API
        add_action('woocommerce_blocks_loaded', [$this, 'register_blocks_integration']);
        
        // DON'T add automatic fee - let shipping method handle it
        // add_action('woocommerce_cart_calculate_fees', [$this, 'add_shipping_fee'], 20);
        
        // Save shipping method with order
        add_action('woocommerce_checkout_order_processed', [$this, 'save_shipping_method'], 10, 1);
        
        error_log('[MWF Blocks] Blocks integration initialized');
    }
    
    /**
     * Register custom shipping method
     */
    public function register_shipping_method($methods) {
        require_once plugin_dir_path(__FILE__) . 'class-mwf-shipping-method.php';
        $methods['mwf_shipping'] = 'MWF_Shipping_Method';
        return $methods;
    }
    
    /**
     * Register blocks integration
     */
    public function register_blocks_integration() {
        if (class_exists('Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface')) {
            require_once plugin_dir_path(__FILE__) . 'class-mwf-checkout-block.php';
            
            add_action(
                'woocommerce_blocks_checkout_block_registration',
                function($integration_registry) {
                    $integration_registry->register(new MWF_Checkout_Block());
                }
            );
        }
    }
    
    /**
     * Add shipping fee to cart
     */
    public function add_shipping_fee() {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }
        
        $shipping_cost = $this->get_shipping_cost_from_cart();
        
        if ($shipping_cost > 0) {
            WC()->cart->add_fee(__('Delivery', 'mwf-subscriptions'), $shipping_cost);
        }
    }
    
    /**
     * Get shipping cost based on variation attributes
     */
    private function get_shipping_cost_from_cart() {
        $cart = WC()->cart;
        if (!$cart || $cart->is_empty()) {
            return 0;
        }
        
        // Check if collection is selected
        if (WC()->session && WC()->session->get('mwf_shipping_method') === 'collection') {
            return 0;
        }
        
        // Calculate shipping based on variation attributes
        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            $product = $cart_item['data'];
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
                    if (strpos($key, 'attribute_pa_frequency') !== false || strpos($key, 'attribute_frequency') !== false) {
                        $frequency = $value;
                    }
                    if (strpos($key, 'attribute_pa_payment-schedule') !== false || strpos($key, 'attribute_payment-schedule') !== false) {
                        $payment_schedule = $value;
                    }
                }
            }
            
            // Calculate shipping cost based on frequency and payment schedule
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
        
        return 0;
    }
    
    /**
     * Save shipping method with order
     */
    public function save_shipping_method($order_id) {
        $shipping_method = WC()->session ? WC()->session->get('mwf_shipping_method') : '';
        
        if ($shipping_method) {
            update_post_meta($order_id, '_mwf_shipping_method', sanitize_text_field($shipping_method));
        }
    }
}
