<?php
/**
 * Box Customization Handler
 * Integrates with Laravel API for drag-and-drop box customization
 */

if (!defined('ABSPATH')) {
    exit;
}

class MWF_Box_Customization {
    
    private static $instance = null;
    private $api_client;
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->api_client = MWF_API_Client::instance();
        $this->init_hooks();
    }
    
    private function init_hooks() {
        // Add My Account tab - higher priority to ensure it shows
        add_filter('woocommerce_account_menu_items', [$this, 'add_box_customization_tab'], 99);
        add_action('init', [$this, 'add_box_customization_endpoint']);
        add_action('woocommerce_account_customize-box_endpoint', [$this, 'render_box_customization_page']);
        
        // AJAX handlers
        add_action('wp_ajax_mwf_get_available_items', [$this, 'ajax_get_available_items']);
        add_action('wp_ajax_mwf_update_box_selection', [$this, 'ajax_update_box_selection']);
        add_action('wp_ajax_mwf_reset_box_to_default', [$this, 'ajax_reset_to_default']);
        add_action('wp_ajax_mwf_get_token_balance', [$this, 'ajax_get_token_balance']);
        
        // Enqueue assets on box customization page
        add_action('wp_enqueue_scripts', [$this, 'enqueue_box_customization_assets']);
    }
    
    /**
     * Add "Customize My Box" tab to My Account
     */
    public function add_box_customization_tab($items) {
        // Insert after downloads or at the end
        $new_items = [];
        $inserted = false;
        
        foreach ($items as $key => $label) {
            $new_items[$key] = $label;
            
            // Insert after downloads
            if ($key === 'downloads' && !$inserted) {
                $new_items['customize-box'] = __('Customize My Box', 'mwf-subscriptions');
                $inserted = true;
            }
        }
        
        // If not inserted yet (no downloads menu item), add at end before logout
        if (!$inserted && isset($new_items['customer-logout'])) {
            $logout = $new_items['customer-logout'];
            unset($new_items['customer-logout']);
            $new_items['customize-box'] = __('Customize My Box', 'mwf-subscriptions');
            $new_items['customer-logout'] = $logout;
        } elseif (!$inserted) {
            // Just add at the end
            $new_items['customize-box'] = __('Customize My Box', 'mwf-subscriptions');
        }
        
        return $new_items;
    }
    
    /**
     * Register endpoint
     */
    public function add_box_customization_endpoint() {
        add_rewrite_endpoint('customize-box', EP_ROOT | EP_PAGES);
    }
    
    /**
     * Render box customization page
     */
    public function render_box_customization_page() {
        $user = wp_get_current_user();
        
        // Get user's active subscription
        $subscription = $this->get_user_active_subscription($user->ID);
        
        if (!$subscription) {
            echo '<div class="woocommerce-notice woocommerce-notice--info">';
            echo '<p>' . __('You don\'t have an active vegbox subscription.', 'mwf-subscriptions') . '</p>';
            echo '<p>' . __('Please purchase a vegbox subscription to use this feature.', 'mwf-subscriptions') . '</p>';
            echo '</div>';
            return;
        }
        
        // Get subscription ID for API calls
        $subscription_id = is_object($subscription) && isset($subscription->id) ? $subscription->id : 0;
        
        // Pass subscription_id to template
        set_query_var('subscription_id', $subscription_id);
        
        include MWF_SUBS_PLUGIN_DIR . 'templates/box-customization.php';
    }
    
    /**
     * Enqueue box customization assets
     */
    public function enqueue_box_customization_assets() {
        if (!is_wc_endpoint_url('customize-box')) {
            return;
        }
        
        // Enqueue drag-and-drop library
        wp_enqueue_script(
            'sortablejs',
            'https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js',
            [],
            '1.15.0',
            true
        );
        
        // Enqueue custom box customization script
        wp_enqueue_script(
            'mwf-box-customization',
            MWF_SUBS_PLUGIN_URL . 'assets/js/box-customization.js',
            ['jquery', 'sortablejs'],
            MWF_SUBS_VERSION,
            true
        );
        
        wp_localize_script('mwf-box-customization', 'mwfBoxCustomization', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mwf_box_customization'),
            'api_url' => MWF_SUBS_API_URL,
            'strings' => [
                'loading' => __('Loading...', 'mwf-subscriptions'),
                'save_success' => __('Box customization saved successfully!', 'mwf-subscriptions'),
                'save_error' => __('Failed to save box customization.', 'mwf-subscriptions'),
                'over_budget' => __('You\'ve exceeded your token budget!', 'mwf-subscriptions'),
                'confirm_reset' => __('Are you sure you want to reset to default selections?', 'mwf-subscriptions'),
                'tokens_remaining' => __('Tokens Remaining:', 'mwf-subscriptions'),
            ]
        ]);
        
        // Enqueue styles
        wp_enqueue_style(
            'mwf-box-customization',
            MWF_SUBS_PLUGIN_URL . 'assets/css/box-customization.css',
            [],
            MWF_SUBS_VERSION
        );
    }
    
    /**
     * AJAX: Get available items for week
     */
    public function ajax_get_available_items() {
        check_ajax_referer('mwf_box_customization', 'nonce');
        
        $subscription_id = isset($_POST['subscription_id']) ? intval($_POST['subscription_id']) : 0;
        $week = isset($_POST['week']) ? sanitize_text_field($_POST['week']) : '';
        
        if (!$subscription_id) {
            wp_send_json_error(['message' => 'Invalid subscription ID']);
        }
        
        $response = $this->api_client->get("box-customization/available-items/{$subscription_id}", [
            'week' => $week
        ]);
        
        if ($response && isset($response['success']) && $response['success']) {
            wp_send_json_success($response['data']);
        } else {
            wp_send_json_error([
                'message' => isset($response['message']) ? $response['message'] : 'Failed to load items'
            ]);
        }
    }
    
    /**
     * AJAX: Update box selection
     */
    public function ajax_update_box_selection() {
        check_ajax_referer('mwf_box_customization', 'nonce');
        
        $subscription_id = isset($_POST['subscription_id']) ? intval($_POST['subscription_id']) : 0;
        $selection_id = isset($_POST['selection_id']) ? intval($_POST['selection_id']) : 0;
        $items = isset($_POST['items']) ? json_decode(stripslashes($_POST['items']), true) : [];
        
        if (!$subscription_id || !$selection_id) {
            wp_send_json_error(['message' => 'Invalid parameters']);
        }
        
        $response = $this->api_client->post("box-customization/update/{$subscription_id}", [
            'selection_id' => $selection_id,
            'items' => $items
        ]);
        
        if ($response && isset($response['success']) && $response['success']) {
            wp_send_json_success($response['data']);
        } else {
            wp_send_json_error([
                'message' => isset($response['message']) ? $response['message'] : 'Failed to update box'
            ]);
        }
    }
    
    /**
     * AJAX: Reset to default
     */
    public function ajax_reset_to_default() {
        check_ajax_referer('mwf_box_customization', 'nonce');
        
        $subscription_id = isset($_POST['subscription_id']) ? intval($_POST['subscription_id']) : 0;
        $selection_id = isset($_POST['selection_id']) ? intval($_POST['selection_id']) : 0;
        
        if (!$subscription_id || !$selection_id) {
            wp_send_json_error(['message' => 'Invalid parameters']);
        }
        
        $response = $this->api_client->post("box-customization/reset/{$subscription_id}", [
            'selection_id' => $selection_id
        ]);
        
        if ($response && isset($response['success']) && $response['success']) {
            wp_send_json_success();
        } else {
            wp_send_json_error([
                'message' => isset($response['message']) ? $response['message'] : 'Failed to reset box'
            ]);
        }
    }
    
    /**
     * AJAX: Get token balance
     */
    public function ajax_get_token_balance() {
        check_ajax_referer('mwf_box_customization', 'nonce');
        
        $subscription_id = isset($_POST['subscription_id']) ? intval($_POST['subscription_id']) : 0;
        
        if (!$subscription_id) {
            wp_send_json_error(['message' => 'Invalid subscription ID']);
        }
        
        $response = $this->api_client->get("box-customization/token-balance/{$subscription_id}");
        
        if ($response && isset($response['success']) && $response['success']) {
            wp_send_json_success($response['data']);
        } else {
            wp_send_json_error([
                'message' => isset($response['message']) ? $response['message'] : 'Failed to load token balance'
            ]);
        }
    }
    
    /**
     * Get user's active subscription
     */
    public function get_user_active_subscription($user_id) {
        // Check if WooCommerce Subscriptions is available
        if (function_exists('wcs_get_users_subscriptions')) {
            $subscriptions = wcs_get_users_subscriptions($user_id);
            
            foreach ($subscriptions as $subscription) {
                if ($subscription->has_status('active')) {
                    // Check if it's a vegbox subscription
                    foreach ($subscription->get_items() as $item) {
                        $product = $item->get_product();
                        if ($product && in_array($product->get_id(), [226084, 226083, 226081, 226082])) {
                            return $subscription;
                        }
                    }
                }
            }
        } else {
            // Fallback: Get active orders with subscription products
            $orders = wc_get_orders([
                'customer_id' => $user_id,
                'limit' => 20,
                'status' => ['completed', 'processing'],
                'orderby' => 'date',
                'order' => 'DESC',
            ]);
            
            foreach ($orders as $order) {
                foreach ($order->get_items() as $item) {
                    $product = $item->get_product();
                    if ($product && in_array($product->get_id(), [226084, 226083, 226081, 226082])) {
                        // Return a mock subscription object with necessary data
                        return (object) [
                            'id' => $order->get_id(),
                            'order' => $order,
                            'user_id' => $user_id,
                        ];
                    }
                }
            }
        }
        
        return null;
    }
}
