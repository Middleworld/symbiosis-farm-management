<?php
/**
 * MWF Shipping Calculator
 * 
 * Dynamically calculates shipping costs based on subscription variation attributes
 */

if (!defined('ABSPATH')) {
    exit;
}

class MWF_Shipping_Calculator {
    
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
        // Add shipping cost as cart fee based on variation selection
        add_action('woocommerce_cart_calculate_fees', [$this, 'add_shipping_fee'], 20);
        
        // Display shipping options - try multiple hook locations
        add_action('woocommerce_checkout_before_customer_details', [$this, 'display_shipping_options'], 10);
        add_action('woocommerce_checkout_billing', [$this, 'display_shipping_options'], 25);
        add_action('woocommerce_checkout_after_customer_details', [$this, 'display_shipping_options'], 10);
        
        // Save selected shipping method with order
        add_action('woocommerce_checkout_update_order_meta', [$this, 'save_shipping_method'], 10);
        
        // Add JavaScript for shipping selection
        add_action('wp_footer', [$this, 'add_shipping_selector_script']);
        
        // Debug: Log when class is initialized
        error_log('[MWF Shipping] Shipping calculator initialized');
    }
    
    /**
     * Get shipping cost based on variation attributes
     */
    private function get_shipping_cost_from_cart() {
        $shipping_cost = 0;
        $shipping_method = 'collection'; // Default to collection (free)
        
        foreach (WC()->cart->get_cart() as $cart_item) {
            $product = $cart_item['data'];
            
            // Check if this is a vegbox subscription
            $is_vegbox = get_post_meta($product->get_id(), '_is_vegbox_subscription', true) === 'yes';
            
            if (!$is_vegbox) {
                continue;
            }
            
            // Get variation attributes
            $variation = $cart_item['variation'] ?? [];
            $frequency = $variation['attribute_frequency'] ?? '';
            $payment_schedule = $variation['attribute_payment-schedule'] ?? '';
            
            // Determine shipping cost based on payment schedule
            // Collection is always free, delivery costs vary by schedule
            $shipping_costs = [
                'weekly' => 4.00,           // Single week delivery
                'fortnightly' => 4.00,      // Single fortnightly delivery  
                'monthly_weekly' => 19.00,  // Monthly payment, weekly delivery
                'monthly_fortnightly' => 9.00, // Monthly payment, fortnightly delivery
                'annually_weekly' => 132.00,    // Annual payment, weekly delivery
                'annually_fortnightly' => 66.00, // Annual payment, fortnightly delivery
            ];
            
            // Calculate which shipping option this is
            if ($payment_schedule === 'weekly' && $frequency === 'weekly') {
                $shipping_cost = $shipping_costs['weekly'];
                $shipping_method = 'single-delivery';
            } elseif ($payment_schedule === 'fortnightly' && $frequency === 'fortnightly') {
                $shipping_cost = $shipping_costs['fortnightly'];
                $shipping_method = 'single-delivery';
            } elseif ($payment_schedule === 'monthly' && $frequency === 'weekly') {
                $shipping_cost = $shipping_costs['monthly_weekly'];
                $shipping_method = 'monthly-payment-weekly-delivery';
            } elseif ($payment_schedule === 'monthly' && $frequency === 'fortnightly') {
                $shipping_cost = $shipping_costs['monthly_fortnightly'];
                $shipping_method = 'monthly-payment-fortnightly-delivery';
            } elseif ($payment_schedule === 'annually' && $frequency === 'weekly') {
                $shipping_cost = $shipping_costs['annually_weekly'];
                $shipping_method = 'annual-payment-weekly-delivery';
            } elseif ($payment_schedule === 'annually' && $frequency === 'fortnightly') {
                $shipping_cost = $shipping_costs['annually_fortnightly'];
                $shipping_method = 'annual-payment-fortnightly-delivery';
            }
            
            // Store in session for checkout display
            WC()->session->set('mwf_default_shipping_method', $shipping_method);
            WC()->session->set('mwf_default_shipping_cost', $shipping_cost);
            
            break; // Only process first vegbox in cart
        }
        
        // Check if user has selected collection
        $selected_shipping = WC()->session->get('mwf_selected_shipping_method');
        if ($selected_shipping === 'collection') {
            return 0;
        }
        
        return $shipping_cost;
    }
    
    /**
     * Add shipping cost as cart fee
     */
    public function add_shipping_fee() {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }
        
        $shipping_cost = $this->get_shipping_cost_from_cart();
        
        if ($shipping_cost > 0) {
            $fee_label = __('Delivery', 'mwf-subscriptions');
            WC()->cart->add_fee($fee_label, $shipping_cost, false);
        }
    }
    
    /**
     * Display shipping options in checkout
     */
    public function display_shipping_options() {
        // ALWAYS output something to verify the hook is firing
        echo '<!-- MWF Shipping: display_shipping_options() CALLED -->';
        
        // Prevent duplicate display
        static $displayed = false;
        if ($displayed) {
            echo '<!-- MWF Shipping: Already displayed, skipping -->';
            return;
        }
        
        // Check if we're on checkout page
        if (!is_checkout()) {
            echo '<!-- MWF Shipping: Not on checkout page -->';
            return;
        }
        
        $displayed = true;
        
        // Check if WooCommerce cart is available
        if (!WC() || !WC()->cart) {
            error_log('[MWF Shipping] WooCommerce cart not available');
            return;
        }
        
        // Get shipping cost and method from cart
        $shipping_cost = 0;
        $has_vegbox = false;
        
        foreach (WC()->cart->get_cart() as $cart_item) {
            $product = $cart_item['data'];
            $product_id = $product->get_parent_id() ? $product->get_parent_id() : $product->get_id();
            
            // Check if this is a vegbox subscription
            $is_vegbox = get_post_meta($product_id, '_is_vegbox_subscription', true);
            
            error_log('[MWF Shipping] Product ID: ' . $product_id . ', Is Vegbox: ' . $is_vegbox);
            
            if ($is_vegbox === 'yes') {
                $has_vegbox = true;
                
                // Get variation attributes
                $variation = $cart_item['variation'] ?? [];
                $frequency = $variation['attribute_frequency'] ?? '';
                $payment_schedule = $variation['attribute_payment-schedule'] ?? '';
                
                error_log('[MWF Shipping] Frequency: ' . $frequency . ', Payment: ' . $payment_schedule);
                
                // Calculate shipping cost based on payment schedule
                if ($payment_schedule === 'weekly' && $frequency === 'weekly') {
                    $shipping_cost = 4.00;
                } elseif ($payment_schedule === 'fortnightly' && $frequency === 'fortnightly') {
                    $shipping_cost = 4.00;
                } elseif ($payment_schedule === 'monthly' && $frequency === 'weekly') {
                    $shipping_cost = 19.00;
                } elseif ($payment_schedule === 'monthly' && $frequency === 'fortnightly') {
                    $shipping_cost = 9.00;
                } elseif ($payment_schedule === 'annually' && $frequency === 'weekly') {
                    $shipping_cost = 132.00;
                } elseif ($payment_schedule === 'annually' && $frequency === 'fortnightly') {
                    $shipping_cost = 66.00;
                }
                
                break;
            }
        }
        
        if (!$has_vegbox) {
            error_log('[MWF Shipping] No vegbox found in cart');
            return;
        }
        
        // Get selected method from POST or default to collection
        $selected_method = 'collection';
        if (isset($_POST['mwf_shipping_method_selected'])) {
            $selected_method = sanitize_text_field($_POST['mwf_shipping_method_selected']);
        }
        
        error_log('[MWF Shipping] Rendering shipping options - Cost: £' . $shipping_cost . ', Method: ' . $selected_method);
        
        ?>
        <div class="mwf-shipping-options" style="margin: 20px 0; padding: 20px; background: #f9f9f9; border: 2px solid #28a745; border-radius: 8px;">
            <h3 style="margin-top: 0; color: #333; font-size: 1.3em;"><?php _e('🚚 Delivery Method', 'mwf-subscriptions'); ?></h3>
            <p style="color: #666; margin-bottom: 15px;">Choose how you'd like to receive your veg box:</p>
            <div style="margin: 15px 0;">
                <label style="display: block; margin-bottom: 12px; padding: 15px; background: white; border: 2px solid #ddd; border-radius: 6px; cursor: pointer; transition: all 0.2s;">
                    <input type="radio" name="mwf_shipping_method" value="collection" <?php checked($selected_method, 'collection'); ?> style="margin-right: 10px;"/>
                    <strong style="font-size: 1.1em;"><?php _e('Collection from the farm', 'mwf-subscriptions'); ?></strong> 
                    <span style="color: #28a745; font-weight: bold; font-size: 1.1em;">FREE ✓</span>
                    <br><small style="margin-left: 24px; color: #666;"><?php _e('📍 Collect your box on Saturdays between 9am-5pm', 'mwf-subscriptions'); ?></small>
                </label>
                
                <label style="display: block; padding: 15px; background: white; border: 2px solid #ddd; border-radius: 6px; cursor: pointer; transition: all 0.2s;">
                    <input type="radio" name="mwf_shipping_method" value="delivery" <?php checked($selected_method, 'delivery'); ?> style="margin-right: 10px;"/>
                    <strong style="font-size: 1.1em;"><?php _e('Delivery to your door', 'mwf-subscriptions'); ?></strong> 
                    <span style="font-weight: bold; font-size: 1.1em;">£<?php echo number_format($shipping_cost, 2); ?></span>
                    <br><small style="margin-left: 24px; color: #666;"><?php _e('🚚 Delivered on Thursdays (cost based on your subscription plan)', 'mwf-subscriptions'); ?></small>
                </label>
            </div>
            <input type="hidden" id="mwf_shipping_method_hidden" name="mwf_shipping_method_selected" value="<?php echo esc_attr($selected_method); ?>"/>
        </div>
        <style>
            .mwf-shipping-options label:hover {
                border-color: #28a745 !important;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            }
            .mwf-shipping-options input[type="radio"]:checked + strong {
                color: #28a745;
            }
        </style>
        <?php
    }
    
    /**
     * Save shipping method with order
     */
    public function save_shipping_method($order_id) {
        $shipping_method = isset($_POST['mwf_shipping_method_selected']) ? sanitize_text_field($_POST['mwf_shipping_method_selected']) : 'collection';
        
        update_post_meta($order_id, '_mwf_shipping_method', $shipping_method);
        
        // Also update the standard WooCommerce shipping method
        $order = wc_get_order($order_id);
        if ($order) {
            $shipping_title = $shipping_method === 'collection' ? 'Collection from the farm' : 'Delivery';
            $order->set_shipping_method($shipping_title);
            $order->save();
        }
    }
    
    /**
     * Add JavaScript for shipping selection
     */
    public function add_shipping_selector_script() {
        if (!is_checkout()) {
            return;
        }
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('input[name="mwf_shipping_method"]').on('change', function() {
                var selected = $(this).val();
                $('#mwf_shipping_method_hidden').val(selected);
                
                // Store in session via AJAX
                $.ajax({
                    url: wc_checkout_params.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'mwf_update_shipping_method',
                        shipping_method: selected,
                        security: '<?php echo wp_create_nonce('mwf_shipping_nonce'); ?>'
                    },
                    success: function() {
                        // Trigger cart update to recalculate fees
                        $('body').trigger('update_checkout');
                    }
                });
            });
        });
        </script>
        <?php
    }
}

// AJAX handler for shipping method updates
add_action('wp_ajax_mwf_update_shipping_method', 'mwf_ajax_update_shipping_method');
add_action('wp_ajax_nopriv_mwf_update_shipping_method', 'mwf_ajax_update_shipping_method');

function mwf_ajax_update_shipping_method() {
    check_ajax_referer('mwf_shipping_nonce', 'security');
    
    $shipping_method = isset($_POST['shipping_method']) ? sanitize_text_field($_POST['shipping_method']) : 'collection';
    WC()->session->set('mwf_selected_shipping_method', $shipping_method);
    
    wp_send_json_success();
}
