<?php
/**
 * Plugin Name: MWF Solidarity Pricing
 * Plugin URI: https://middleworldfarms.org
 * Description: Beautiful splash modal explaining the pay-what-you-can solidarity pricing model. Shows on first vegbox product click.
 * Version: 1.1.9
 * Author: Middle World Farms
 * Author URI: https://middleworldfarms.org
 * License: GPL v2 or later
 * Text Domain: mwf-solidarity-pricing
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('MWF_SOLIDARITY_VERSION', '1.1.9');
define('MWF_SOLIDARITY_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MWF_SOLIDARITY_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Main plugin class
 */
class MWF_Solidarity_Pricing {
    
    private static $instance = null;
    
    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Enqueue assets
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        
        // Add modal HTML to footer
        add_action('wp_footer', [$this, 'render_modal']);
        
        // Add shop page banner - try multiple hooks
        add_action('woocommerce_before_shop_loop', [$this, 'render_shop_banner'], 5);
        add_action('woocommerce_before_main_content', [$this, 'render_shop_banner'], 15);
        
        // Register shortcode for homepage section
        add_shortcode('mwf_solidarity_section', [$this, 'render_homepage_section']);
        
        // WooCommerce integration for solidarity pricing
        add_action('woocommerce_after_variations_table', [$this, 'render_solidarity_slider'], 5);
        add_filter('woocommerce_add_cart_item_data', [$this, 'add_custom_price_to_cart'], 10, 3);
        add_filter('woocommerce_get_item_data', [$this, 'display_custom_price_in_cart'], 10, 2);
        add_action('woocommerce_before_calculate_totals', [$this, 'apply_custom_price_to_cart_item'], 10, 1);
        
        // Remove solidarity level dropdown - pricing handled by slider
        add_filter('woocommerce_product_get_attributes', [$this, 'remove_solidarity_attribute'], 10, 2);
        
        // Admin settings
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
    }
    
    /**
     * Enqueue CSS and JavaScript
     */
    public function enqueue_assets() {
        // Only load on shop and product pages, or if shortcode is used
        if (!is_shop() && !is_product() && !has_shortcode(get_post()->post_content ?? '', 'mwf_solidarity_section')) {
            return;
        }
        
        // Main modal styles
        wp_enqueue_style(
            'mwf-solidarity-pricing',
            MWF_SOLIDARITY_PLUGIN_URL . 'assets/css/solidarity-modal.css',
            [],
            MWF_SOLIDARITY_VERSION
        );
        
        // Homepage section styles
        if (has_shortcode(get_post()->post_content ?? '', 'mwf_solidarity_section')) {
            wp_enqueue_style(
                'mwf-solidarity-homepage',
                MWF_SOLIDARITY_PLUGIN_URL . 'assets/css/homepage-section.css',
                [],
                MWF_SOLIDARITY_VERSION
            );
        }
        
        // Price slider styles (only on product pages)
        if (is_product()) {
            wp_enqueue_style(
                'mwf-price-slider',
                MWF_SOLIDARITY_PLUGIN_URL . 'assets/css/price-slider.css',
                [],
                MWF_SOLIDARITY_VERSION
            );
        }
        
        // Shop banner styles (only on shop page)
        if (is_shop()) {
            wp_enqueue_style(
                'mwf-solidarity-shop-banner',
                MWF_SOLIDARITY_PLUGIN_URL . 'assets/css/shop-banner.css',
                [],
                MWF_SOLIDARITY_VERSION
            );
        }
        
        wp_enqueue_script(
            'mwf-solidarity-pricing',
            MWF_SOLIDARITY_PLUGIN_URL . 'assets/js/solidarity-modal.js',
            ['jquery'],
            MWF_SOLIDARITY_VERSION,
            true
        );
        
        // Price slider script (only on product pages)
        if (is_product()) {
            wp_enqueue_script(
                'mwf-price-slider',
                MWF_SOLIDARITY_PLUGIN_URL . 'assets/js/price-slider.js',
                ['jquery'],
                MWF_SOLIDARITY_VERSION,
                true
            );
        }
        
        // Pass settings to JavaScript
        wp_localize_script('mwf-solidarity-pricing', 'mwfSolidarity', [
            'cookieName' => 'mwf_solidarity_seen',
            'cookieDays' => get_option('mwf_solidarity_cookie_days', 30),
            'showOnShop' => get_option('mwf_solidarity_show_shop', 'no') === 'yes',
            'vegboxCategories' => $this->get_vegbox_categories(),
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mwf_solidarity_nonce')
        ]);
    }
    
    /**
     * Get vegbox category IDs
     */
    private function get_vegbox_categories() {
        $category_slugs = ['vegetable-boxes', 'veg-boxes', 'vegbox'];
        $category_ids = [];
        
        foreach ($category_slugs as $slug) {
            $term = get_term_by('slug', $slug, 'product_cat');
            if ($term) {
                $category_ids[] = $term->term_id;
            }
        }
        
        return $category_ids;
    }
    
    /**
     * Render modal HTML
     */
    public function render_modal() {
        // Render on all pages (needed for homepage solidarity pricing section)
        
        $settings = $this->get_settings();
        
        // Get dynamic pricing based on product
        $pricing = $this->get_dynamic_pricing();
        
        include MWF_SOLIDARITY_PLUGIN_DIR . 'templates/modal.php';
    }
    
    /**
     * Get dynamic pricing based on current product
     */
    private function get_dynamic_pricing() {
        global $product;
        
        // Default to couples box pricing
        $base_price = 15;
        $box_type = 'couples';
        
        // Detect product type from title or slug
        if (is_product() && $product) {
            $title = strtolower($product->get_name());
            $slug = $product->get_slug();
            
            if (strpos($title, 'single') !== false || strpos($slug, 'single') !== false) {
                $base_price = 10;
                $box_type = 'single';
            } elseif (strpos($title, 'small family') !== false || strpos($slug, 'small-family') !== false) {
                $base_price = 22;
                $box_type = 'small-family';
            } elseif (strpos($title, 'large family') !== false || strpos($slug, 'large-family') !== false) {
                $base_price = 25;
                $box_type = 'large-family';
            } elseif (strpos($title, 'couple') !== false || strpos($slug, 'couple') !== false) {
                $base_price = 15;
                $box_type = 'couples';
            }
        }
        
        // Calculate pricing tiers
        $solidarity_min = round($base_price * 0.70, 2); // 70% of standard
        $solidarity_max = round($base_price * 0.93, 2); // 93% of standard
        $supporter_min = round($base_price * 1.20, 2); // 120% of standard
        $supporter_mid = round($base_price * 1.33, 2); // 133% of standard
        $supporter_max = round($base_price * 1.67, 2); // 167% of standard
        
        // Calculate annual costs (scaled to box size)
        $members = 100;
        $weeks = 48;
        
        // Use fixed budget based on Couple's box (most popular)
        // Keeping it simple rather than complex per-box calculations
        $annual_wages = 45000;
        $annual_seeds = 2000;
        $annual_land = 4800;
        $annual_equipment = 4000;
        $annual_packaging = 200;
        $annual_admin = 700;
        
        $annual_total = $annual_wages + $annual_seeds + $annual_land + $annual_equipment + $annual_packaging + $annual_admin;
        
        // Minimum per box to survive
        $minimum_per_box = round($annual_total / $members / $weeks, 2);
        
        return [
            'box_type' => $box_type,
            'base_price' => $base_price,
            'solidarity_min' => $solidarity_min,
            'solidarity_max' => $solidarity_max,
            'solidarity_range' => '£' . number_format($solidarity_min, 2) . ' - £' . number_format($solidarity_max, 2),
            'standard_price' => '£' . number_format($base_price, 2),
            'supporter_min' => $supporter_min,
            'supporter_mid' => $supporter_mid,
            'supporter_max' => $supporter_max,
            'supporter_range' => '£' . number_format($supporter_min, 2) . ' - £' . number_format($supporter_max, 2) . '+',
            
            // Annual totals (formatted for display)
            'annual_wages' => number_format($annual_wages),
            'annual_seeds' => number_format($annual_seeds),
            'annual_land' => number_format($annual_land),
            'annual_equipment' => number_format($annual_equipment),
            'annual_packaging' => number_format($annual_packaging),
            'annual_admin' => number_format($annual_admin),
            'annual_total' => number_format($annual_total),
            
            // Math
            'members' => $members,
            'weeks' => $weeks,
            'minimum_per_box' => number_format($minimum_per_box, 2),
        ];
    }
    
    /**
     * Render shop page banner
     */
    public function render_shop_banner() {
        if (!is_shop()) {
            return;
        }
        
        // Check if banner is enabled
        if (get_option('mwf_solidarity_show_banner', 'yes') !== 'yes') {
            return;
        }
        
        ?>
        <div class="mwf-solidarity-shop-banner">
            <h3>💚 Pay What You Can 🌱</h3>
            <p>Choose a price that feels right for your circumstances. Everyone receives the same quality, organic produce.</p>
            
            <div class="mwf-solidarity-shop-icons">
                <div class="mwf-solidarity-shop-icon">
                    <span class="mwf-solidarity-shop-icon-emoji">💚</span>
                    <span class="mwf-solidarity-shop-icon-label">Solidarity</span>
                </div>
                <div class="mwf-solidarity-shop-icon">
                    <span class="mwf-solidarity-shop-icon-emoji">🌱</span>
                    <span class="mwf-solidarity-shop-icon-label">Standard</span>
                </div>
                <div class="mwf-solidarity-shop-icon">
                    <span class="mwf-solidarity-shop-icon-emoji">🌳</span>
                    <span class="mwf-solidarity-shop-icon-label">Supporter</span>
                </div>
            </div>
            
            <a href="#" class="mwf-solidarity-learn-link" onclick="jQuery('.mwf-solidarity-overlay').addClass('active'); return false;">
                Learn More About Our Food Solidarity Model →
            </a>
        </div>
        <?php
    }
    
    /**
     * Get plugin settings with defaults
     */
    private function get_settings() {
        return [
            'enabled' => get_option('mwf_solidarity_enabled', 'yes') === 'yes',
            'headline' => get_option('mwf_solidarity_headline', 'Food Belongs to Everyone'),
            'subheadline' => get_option('mwf_solidarity_subheadline', 'Choose what you can afford. Everyone receives the same quality.'),
            'solidarity_label' => get_option('mwf_solidarity_label', 'Solidarity Price'),
            'solidarity_desc' => get_option('mwf_solidarity_desc', 'For those who need support'),
            'solidarity_price' => get_option('mwf_solidarity_price', '£10.50+'),
            'standard_label' => get_option('mwf_standard_label', 'Standard Price'),
            'standard_desc' => get_option('mwf_standard_desc', 'True cost of growing'),
            'standard_price' => get_option('mwf_standard_price', '£15'),
            'supporter_label' => get_option('mwf_supporter_label', 'Supporter Price'),
            'supporter_desc' => get_option('mwf_supporter_desc', 'Helps subsidize others'),
            'supporter_price' => get_option('mwf_supporter_price', '£18+'),
            'background_image' => get_option('mwf_solidarity_bg_image', ''),
            'learn_more_text' => get_option('mwf_solidarity_learn_more', $this->get_default_learn_more()),
            'families_count' => get_option('mwf_solidarity_families_count', '23'),
        ];
    }
    
    /**
     * Default "Learn More" content
     */
    private function get_default_learn_more() {
        return "Growing food biologically—without chemicals and with deep care for soil life—takes time, craft, and labour.\n\nOur solidarity model:\n• Creates fair wages for farmers\n• Keeps the farm stable and community-owned\n• Ensures everyone can eat nutrient-rich produce\n• Builds a food system based on trust, not profit\n\nThis is not charity. It's shared responsibility and shared abundance.";
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            'Solidarity Pricing',
            'Solidarity Pricing',
            'manage_woocommerce',
            'mwf-solidarity-pricing',
            [$this, 'render_admin_page']
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('mwf_solidarity_settings', 'mwf_solidarity_enabled');
        register_setting('mwf_solidarity_settings', 'mwf_solidarity_headline');
        register_setting('mwf_solidarity_settings', 'mwf_solidarity_subheadline');
        register_setting('mwf_solidarity_settings', 'mwf_solidarity_label');
        register_setting('mwf_solidarity_settings', 'mwf_solidarity_desc');
        register_setting('mwf_solidarity_settings', 'mwf_solidarity_price');
        register_setting('mwf_solidarity_settings', 'mwf_standard_label');
        register_setting('mwf_solidarity_settings', 'mwf_standard_desc');
        register_setting('mwf_solidarity_settings', 'mwf_standard_price');
        register_setting('mwf_solidarity_settings', 'mwf_supporter_label');
        register_setting('mwf_solidarity_settings', 'mwf_supporter_desc');
        register_setting('mwf_solidarity_settings', 'mwf_supporter_price');
        register_setting('mwf_solidarity_settings', 'mwf_solidarity_bg_image');
        register_setting('mwf_solidarity_settings', 'mwf_solidarity_learn_more');
        register_setting('mwf_solidarity_settings', 'mwf_solidarity_families_count');
        register_setting('mwf_solidarity_settings', 'mwf_solidarity_cookie_days');
        register_setting('mwf_solidarity_settings', 'mwf_solidarity_show_shop');
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        if (!current_user_can('manage_woocommerce')) {
            return;
        }
        
        include MWF_SOLIDARITY_PLUGIN_DIR . 'templates/admin.php';
    }
    
    /**
     * Render homepage solidarity section (shortcode)
     * Usage: [mwf_solidarity_section]
     */
    public function render_homepage_section($atts) {
        $atts = shortcode_atts([
            'title' => 'Our Solidarity Promise',
            'subtitle' => 'Fair prices for everyone',
        ], $atts);
        
        ob_start();
        include MWF_SOLIDARITY_PLUGIN_DIR . 'templates/homepage-section.php';
        return ob_get_clean();
    }
    
    /**
     * Render solidarity price slider on product pages
     */
    public function render_solidarity_slider() {
        global $product;
        
        // Get product from global or from the loop
        if (!$product) {
            $product = wc_get_product(get_the_ID());
        }
        
        // Only show on veg box products
        if (!$product || !$product->is_type('variable')) {
            return;
        }
        
        // Check if this is a veg box product (has frequency/payment attributes or is in veg box category)
        $attributes = $product->get_attributes();
        $has_frequency = isset($attributes['pa_frequency']) || isset($attributes['frequency']);
        $has_payment_schedule = isset($attributes['pa_payment-schedule']) || isset($attributes['payment-schedule']);
        $has_subscription = isset($attributes['pa_subscription']) || isset($attributes['subscription']);

        // Check if product is in vegetable boxes category or has veg box in title/SKU
        $is_veg_box = false;
        $categories = wp_get_post_terms($product->get_id(), 'product_cat', ['fields' => 'names']);
        if (in_array('Vegetable Boxes', $categories) || stripos($product->get_name(), 'veg') !== false || stripos($product->get_sku(), 'veg') !== false) {
            $is_veg_box = true;
        }

        if (!$has_frequency && !$has_payment_schedule && !$has_subscription && !$is_veg_box) {
            return;
        }
        
        // Get pricing data
        $pricing = $this->get_product_pricing($product);
        
        ?>
        <div class="mwf-solidarity-slider-wrapper">
            <div class="mwf-solidarity-slider-header">
                <h3 class="mwf-solidarity-slider-title">💚 Choose Your Price 🌱</h3>
                <p class="mwf-solidarity-slider-subtitle">Pay what you can afford - everyone gets the same quality</p>
            </div>
            
            <div class="mwf-solidarity-price-display">
                <div class="mwf-solidarity-price-amount" id="mwf-custom-price-display">£<?php echo number_format($pricing['standard'], 2); ?></div>
                <span class="mwf-solidarity-price-label">
                    <span class="zone-icon">🌱</span> 
                    <span class="zone-text">Standard Price</span>
                </span>
            </div>
            
            <div class="mwf-solidarity-slider-container">
                <input 
                    type="range" 
                    class="mwf-solidarity-slider" 
                    id="mwf-solidarity-slider"
                    min="<?php echo $pricing['min']; ?>" 
                    max="<?php echo $pricing['max']; ?>" 
                    step="0.50" 
                    value="<?php echo $pricing['standard']; ?>"
                    data-standard="<?php echo $pricing['standard']; ?>"
                    data-break-even="<?php echo $pricing['break_even']; ?>"
                />
                
                <div class="mwf-solidarity-slider-labels">
                    <div class="mwf-slider-label">
                        <span class="mwf-slider-label-icon">💚</span>
                        <span class="mwf-slider-label-text">Solidarity</span>
                        <span class="mwf-slider-label-price">£<?php echo number_format($pricing['min'], 2); ?></span>
                    </div>
                    <div class="mwf-slider-label">
                        <span class="mwf-slider-label-icon">🌱</span>
                        <span class="mwf-slider-label-text">Standard</span>
                        <span class="mwf-slider-label-price">£<?php echo number_format($pricing['standard'], 2); ?></span>
                    </div>
                    <div class="mwf-slider-label">
                        <span class="mwf-slider-label-icon">🌳</span>
                        <span class="mwf-slider-label-text">Supporter</span>
                        <span class="mwf-slider-label-price">£<?php echo number_format($pricing['max'], 2); ?></span>
                    </div>
                </div>
            </div>
            
            <div class="mwf-solidarity-price-impact">
                <span class="mwf-price-impact-icon">✓</span>
                <span class="mwf-price-impact-text">Fair wage for farmers</span>
            </div>
            
            <!-- Hidden input to store custom price -->
            <input type="hidden" name="mwf_custom_price" id="mwf-custom-price" value="<?php echo $pricing['standard']; ?>" />
        </div>
        <?php
    }
    
    /**
     * Get pricing data for a product
     */
    private function get_product_pricing($product) {
        // Get base price from product variations
        $variations = $product->get_available_variations();
        $standard_price = 15; // Default
        
        // Find standard weekly price
        foreach ($variations as $variation) {
            $attributes = $variation['attributes'];
            // Look for standard price - weekly payment schedule and weekly frequency
            $payment_schedule = isset($attributes['attribute_pa_payment-schedule']) ? $attributes['attribute_pa_payment-schedule'] : 
                               (isset($attributes['attribute_payment-schedule']) ? $attributes['attribute_payment-schedule'] : '');
            $frequency = isset($attributes['attribute_pa_frequency']) ? $attributes['attribute_pa_frequency'] : 
                        (isset($attributes['attribute_frequency']) ? $attributes['attribute_frequency'] : '');
            
            if ($payment_schedule === 'weekly' && $frequency === 'weekly') {
                $standard_price = $variation['display_price'];
                break;
            }
        }        // Calculate pricing range
        $min_price = round($standard_price * 0.7, 2); // 70% of standard
        $max_price = round($standard_price * 2.0, 2); // 200% of standard
        $break_even = round($standard_price * 0.8, 2); // Approximate break-even
        
        return [
            'min' => $min_price,
            'standard' => $standard_price,
            'max' => $max_price,
            'break_even' => $break_even
        ];
    }
    
    /**
     * Remove solidarity level attribute from product display
     */
    public function remove_solidarity_attribute($attributes, $product) {
        // Check if this is a veg box product
        $has_frequency = isset($attributes['pa_frequency']) || isset($attributes['frequency']);
        $has_payment_schedule = isset($attributes['pa_payment-schedule']) || isset($attributes['payment-schedule']);
        $has_subscription = isset($attributes['pa_subscription']) || isset($attributes['subscription']);

        // Check if product is in vegetable boxes category or has veg box in title/SKU
        $is_veg_box = false;
        $categories = wp_get_post_terms($product->get_id(), 'product_cat', ['fields' => 'names']);
        if (in_array('Vegetable Boxes', $categories) || stripos($product->get_name(), 'veg') !== false || stripos($product->get_sku(), 'veg') !== false) {
            $is_veg_box = true;
        }

        if ($product->is_type('variable') && ($has_frequency || $has_payment_schedule || $has_subscription || $is_veg_box)) {
            unset($attributes['pa_solidarity_level']);
            unset($attributes['solidarity_level']);
        }
        return $attributes;
    }
    
    /**
     * Add custom price to cart item data
     */
    public function add_custom_price_to_cart($cart_item_data, $product_id, $variation_id) {
        if (isset($_POST['mwf_custom_price']) && !empty($_POST['mwf_custom_price'])) {
            $cart_item_data['mwf_custom_price'] = floatval($_POST['mwf_custom_price']);
        }
        return $cart_item_data;
    }
    
    /**
     * Display custom price in cart
     */
    public function display_custom_price_in_cart($item_data, $cart_item) {
        if (isset($cart_item['mwf_custom_price'])) {
            $item_data[] = array(
                'name' => 'Your Price',
                'value' => '£' . number_format($cart_item['mwf_custom_price'], 2)
            );
        }
        return $item_data;
    }
    
    /**
     * Apply custom price to cart item
     */
    public function apply_custom_price_to_cart_item($cart) {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }
        
        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            if (isset($cart_item['mwf_custom_price'])) {
                $cart_item['data']->set_price($cart_item['mwf_custom_price']);
            }
        }
    }
}

// Initialize plugin
MWF_Solidarity_Pricing::instance();
