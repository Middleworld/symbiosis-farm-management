<?php
/**
 * Plugin Name: MWF Custom Subscriptions
 * Plugin URI: https://example-farm.com
 * Description: Custom subscription management powered by Laravel backend. Replaces WooCommerce Subscriptions addon.
 * Version: 1.2.1
 * Author: Middle World Farms
 * Author URI: https://example-farm.com
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * WC requires at least: 8.0
 * WC tested up to: 9.4
 * Requires Plugins: woocommerce
 * Text Domain: mwf-subscriptions
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define constants
define('MWF_SUBS_VERSION', '1.2.1');
define('MWF_SUBS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MWF_SUBS_PLUGIN_URL', plugin_dir_url(__FILE__));
// API settings stored in WordPress options
define('MWF_SUBS_API_URL', get_option('mwf_api_url', ''));
define('MWF_SUBS_API_KEY', get_option('mwf_api_key', ''));

// Load core dependencies
require_once MWF_SUBS_PLUGIN_DIR . 'includes/class-mwf-settings.php';
require_once MWF_SUBS_PLUGIN_DIR . 'includes/class-mwf-api-client.php';
require_once MWF_SUBS_PLUGIN_DIR . 'includes/class-mwf-my-account.php';
require_once MWF_SUBS_PLUGIN_DIR . 'includes/class-mwf-checkout.php';
require_once MWF_SUBS_PLUGIN_DIR . 'includes/class-mwf-subscription-updater.php';
require_once MWF_SUBS_PLUGIN_DIR . 'includes/class-mwf-shipping-calculator.php';
require_once MWF_SUBS_PLUGIN_DIR . 'includes/class-mwf-blocks-integration.php';
require_once MWF_SUBS_PLUGIN_DIR . 'includes/class-mwf-admin.php';

// Load product classes after WooCommerce is available
add_action('woocommerce_loaded', function() {
    require_once MWF_SUBS_PLUGIN_DIR . 'includes/class-wc-product-variable-subscription.php';
    require_once MWF_SUBS_PLUGIN_DIR . 'includes/class-mwf-product-manager.php';
});

/**
 * Main plugin class
 */
class MWF_Subscriptions {
    
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
        // Initialize components
        add_action('plugins_loaded', [$this, 'init'], 10);
        
        // Declare HPOS compatibility
        add_action('before_woocommerce_init', [$this, 'declare_hpos_compatibility']);
        
        // Register product type
        add_filter('product_type_selector', [$this, 'add_variable_subscription_type']);
        add_filter('woocommerce_product_class', [$this, 'load_variable_subscription_class'], 10, 4);
        
        // Make variable-subscription behave like variable product
        add_filter('woocommerce_product_type_query', [$this, 'map_product_type'], 10, 2);
        add_filter('woocommerce_product_supports', [$this, 'product_supports'], 10, 3);
        
        // Template override
        add_filter('woocommerce_locate_template', [$this, 'locate_variable_subscription_template'], 10, 3);
        
        // Enqueue assets
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        
        // Activation hook
        register_activation_hook(__FILE__, [$this, 'activate']);
    }
    
    public function init() {
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', [$this, 'woocommerce_missing_notice']);
            return;
        }
        
        // Initialize components
        MWF_My_Account::instance();
        MWF_Checkout::instance();
        MWF_Subscription_Updater::instance();
        MWF_Shipping_Calculator::instance();
        MWF_Blocks_Integration::instance(); // NEW: WooCommerce Blocks support
        
        // Initialize admin interface
        if (is_admin() && class_exists('MWF_Product_Manager')) {
            MWF_Subscriptions_Admin::instance();
            MWF_Product_Manager::instance();
        }
        
        // Log plugin initialization
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[MWF Subscriptions] Plugin v' . MWF_SUBS_VERSION . ' initialized at ' . date('Y-m-d H:i:s'));
        }
    }
    
    public function add_variable_subscription_type($types) {
        $types['variable-subscription'] = __('Variable subscription', 'mwf-subscriptions');
        return $types;
    }
    
    public function load_variable_subscription_class($classname, $product_type, $post_type, $product_id) {
        if ($product_type === 'variable-subscription') {
            $classname = 'WC_Product_Variable_Subscription';
        }
        return $classname;
    }
    
    public function map_product_type($type, $product_id) {
        // Tell WooCommerce to treat variable-subscription as variable
        if ($type === 'variable-subscription') {
            return 'variable';
        }
        return $type;
    }
    
    public function product_supports($supports, $feature, $product) {
        // Make variable-subscription products support ajax_add_to_cart
        if ($product && $product->get_type() === 'variable-subscription') {
            // Don't support ajax for variable products (needs variation selection)
            if ($feature === 'ajax_add_to_cart') {
                return false;
            }
        }
        return $supports;
    }
    
    public function locate_variable_subscription_template($template, $template_name, $template_path) {
        // Map variable-subscription template to variable template
        if (strpos($template_name, 'variable-subscription') !== false) {
            $new_template_name = str_replace('variable-subscription', 'variable', $template_name);
            $new_template = WC()->plugin_path() . '/templates/' . $new_template_name;
            
            if (file_exists($new_template)) {
                return $new_template;
            }
        }
        
        return $template;
    }
    
    public function enqueue_assets() {
        if (is_account_page() || is_checkout()) {
            wp_enqueue_style(
                'mwf-subscriptions',
                MWF_SUBS_PLUGIN_URL . 'assets/css/mwf-subscriptions.css',
                [],
                MWF_SUBS_VERSION
            );
            
            wp_enqueue_script(
                'mwf-subscriptions',
                MWF_SUBS_PLUGIN_URL . 'assets/js/mwf-subscriptions.js',
                ['jquery'],
                MWF_SUBS_VERSION,
                true
            );
            
            wp_localize_script('mwf-subscriptions', 'mwfSubs', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('mwf_subscriptions')
            ]);
        }
    }
    
    public function activate() {
        // Check WooCommerce is active
        if (!class_exists('WooCommerce')) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(__('MWF Custom Subscriptions requires WooCommerce to be installed and active.', 'mwf-subscriptions'));
        }
        
        // 1. Enable WooCommerce REST API
        update_option('woocommerce_api_enabled', 'yes');
        
        // 2. Add WooCommerce capabilities to all administrators
        $admins = get_users(['role' => 'administrator']);
        foreach ($admins as $admin) {
            $admin->add_cap('manage_woocommerce');
            $admin->add_cap('edit_shop_orders');
            $admin->add_cap('read_shop_orders');
            $admin->add_cap('edit_products');
            $admin->add_cap('read_products');
        }
        
        // 3. Set up permalinks if not configured
        $permalink_structure = get_option('permalink_structure');
        if (empty($permalink_structure)) {
            global $wp_rewrite;
            $wp_rewrite->set_permalink_structure('/%year%/%monthnum%/%day%/%postname%/');
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // 4. Sync all variable products
        $variable_products = get_posts([
            'post_type' => 'product',
            'post_status' => 'publish',
            'numberposts' => -1,
            'meta_query' => [
                [
                    'key' => '_product_type',
                    'value' => 'variable'
                ]
            ]
        ]);
        
        foreach ($variable_products as $product_post) {
            $product_id = $product_post->ID;
            $product = wc_get_product($product_id);
            
            if ($product && $product->is_type('variable')) {
                // Sync variations
                WC_Product_Variable::sync($product);
                
                // Update children meta
                $children = get_posts([
                    'post_parent' => $product_id,
                    'post_type' => 'product_variation',
                    'post_status' => 'publish',
                    'numberposts' => -1,
                    'fields' => 'ids'
                ]);
                
                if (!empty($children)) {
                    update_post_meta($product_id, '_children', $children);
                }
            }
        }
        
        // 5. Clear all caches
        wp_cache_flush();
        if (function_exists('wc_delete_product_transients')) {
            foreach ($variable_products as $product_post) {
                wc_delete_product_transients($product_post->ID);
            }
        }
        
        // Log activation
        error_log('[MWF Subscriptions] Plugin v' . MWF_SUBS_VERSION . ' activated at ' . date('Y-m-d H:i:s'));
        error_log('[MWF Subscriptions] Auto-setup completed: API enabled, capabilities added, ' . count($variable_products) . ' products synced');
    }
    
    public function woocommerce_missing_notice() {
        ?>
        <div class="notice notice-error">
            <p><?php _e('MWF Custom Subscriptions requires WooCommerce to be installed and active.', 'mwf-subscriptions'); ?></p>
        </div>
        <?php
    }
    
    /**
     * Declare HPOS compatibility
     */
    public function declare_hpos_compatibility() {
        if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('orders_cache', __FILE__, true);
        }
    }
}

// Initialize plugin
function MWF_Subscriptions() {
    return MWF_Subscriptions::instance();
}

MWF_Subscriptions();
