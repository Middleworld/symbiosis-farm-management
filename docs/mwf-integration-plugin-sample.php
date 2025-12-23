<?php
/**
 * Plugin Name: MWF Integration API
 * Plugin URI: https://middleworldfarms.org
 * Description: Custom REST API endpoints for Middle World Farms admin integration
 * Version: 1.0.0
 * Author: Middle World Farms
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: mwf-integration
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('MWF_INTEGRATION_VERSION', '1.0.0');
define('MWF_INTEGRATION_API_NAMESPACE', 'mwf-integration/v1');

/**
 * Main MWF Integration Class
 */
class MWF_Integration_API {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * Register REST API routes
     */
    public function register_routes() {
        register_rest_route(MWF_INTEGRATION_API_NAMESPACE, '/products/(?P<id>\d+)/edit', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_product_for_edit'),
            'permission_callback' => array($this, 'check_permissions'),
            'args' => array(
                'id' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ),
            ),
        ));

        register_rest_route(MWF_INTEGRATION_API_NAMESPACE, '/products/(?P<id>\d+)', array(
            'methods' => 'PUT',
            'callback' => array($this, 'update_product'),
            'permission_callback' => array($this, 'check_permissions'),
            'args' => array(
                'id' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ),
            ),
        ));

        register_rest_route(MWF_INTEGRATION_API_NAMESPACE, '/products/(?P<id>\d+)/variations', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_product_variations'),
            'permission_callback' => array($this, 'check_permissions'),
        ));

        register_rest_route(MWF_INTEGRATION_API_NAMESPACE, '/products/bulk-update', array(
            'methods' => 'POST',
            'callback' => array($this, 'bulk_update_products'),
            'permission_callback' => array($this, 'check_permissions'),
        ));

        register_rest_route(MWF_INTEGRATION_API_NAMESPACE, '/capabilities', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_capabilities'),
            'permission_callback' => array($this, 'check_permissions'),
        ));

        register_rest_route(MWF_INTEGRATION_API_NAMESPACE, '/actions', array(
            'methods' => 'POST',
            'callback' => array($this, 'execute_action'),
            'permission_callback' => array($this, 'check_permissions'),
        ));
    }

    /**
     * Check API permissions
     */
    public function check_permissions($request) {
        $api_key = $request->get_header('Authorization');

        if (!$api_key) {
            return false;
        }

        // Remove "Bearer " prefix if present
        $api_key = str_replace('Bearer ', '', $api_key);

        // Check against stored API key
        $stored_key = get_option('mwf_integration_api_key', '');

        return hash_equals($stored_key, $api_key);
    }

    /**
     * Get enhanced product data for editing
     */
    public function get_product_for_edit($request) {
        $product_id = $request->get_param('id');

        $product = wc_get_product($product_id);

        if (!$product) {
            return new WP_Error('product_not_found', 'Product not found', array('status' => 404));
        }

        // Get enhanced product data
        $product_data = array(
            'id' => $product->get_id(),
            'name' => $product->get_name(),
            'sku' => $product->get_sku(),
            'description' => $product->get_description(),
            'short_description' => $product->get_short_description(),
            'price' => $product->get_price(),
            'regular_price' => $product->get_regular_price(),
            'sale_price' => $product->get_sale_price(),
            'stock_quantity' => $product->get_stock_quantity(),
            'manage_stock' => $product->get_manage_stock(),
            'stock_status' => $product->get_stock_status(),
            'categories' => $this->get_product_categories($product),
            'images' => $this->get_product_images($product),
            'attributes' => $this->get_product_attributes($product),
            'variations' => $product->is_type('variable') ? $this->get_product_variations_data($product) : array(),
            'meta_data' => $this->get_product_meta($product),
            'product_type' => $product->get_type(),
            'status' => $product->get_status(),
            'featured' => $product->get_featured(),
            'catalog_visibility' => $product->get_catalog_visibility(),
            'purchase_note' => $product->get_purchase_note(),
            'menu_order' => $product->get_menu_order(),
            'reviews_allowed' => $product->get_reviews_allowed(),
            'virtual' => $product->get_virtual(),
            'downloadable' => $product->get_downloadable(),
            'downloads' => $product->get_downloads(),
            'download_limit' => $product->get_download_limit(),
            'download_expiry' => $product->get_download_expiry(),
            'tax_status' => $product->get_tax_status(),
            'tax_class' => $product->get_tax_class(),
            'weight' => $product->get_weight(),
            'length' => $product->get_length(),
            'width' => $product->get_width(),
            'height' => $product->get_height(),
            'shipping_class_id' => $product->get_shipping_class_id(),
            'sold_individually' => $product->get_sold_individually(),
            'backorders' => $product->get_backorders(),
            'low_stock_amount' => $product->get_low_stock_amount(),
            'upsell_ids' => $product->get_upsell_ids(),
            'cross_sell_ids' => $product->get_cross_sell_ids(),
            'parent_id' => $product->get_parent_id(),
            'grouped_products' => $product->is_type('grouped') ? $product->get_children() : array(),
            'bundle_products' => $product->is_type('bundle') ? $this->get_bundle_data($product) : array(),
        );

        return new WP_REST_Response($product_data, 200);
    }

    /**
     * Update product
     */
    public function update_product($request) {
        $product_id = $request->get_param('id');
        $data = $request->get_json_params();

        $product = wc_get_product($product_id);

        if (!$product) {
            return new WP_Error('product_not_found', 'Product not found', array('status' => 404));
        }

        try {
            // Update basic fields
            if (isset($data['name'])) $product->set_name($data['name']);
            if (isset($data['description'])) $product->set_description($data['description']);
            if (isset($data['short_description'])) $product->set_short_description($data['short_description']);
            if (isset($data['price'])) $product->set_price($data['price']);
            if (isset($data['regular_price'])) $product->set_regular_price($data['regular_price']);
            if (isset($data['sale_price'])) $product->set_sale_price($data['sale_price']);
            if (isset($data['sku'])) $product->set_sku($data['sku']);
            if (isset($data['stock_quantity'])) $product->set_stock_quantity($data['stock_quantity']);
            if (isset($data['manage_stock'])) $product->set_manage_stock($data['manage_stock']);
            if (isset($data['stock_status'])) $product->set_stock_status($data['stock_status']);

            // Update categories if provided
            if (isset($data['categories'])) {
                $product->set_category_ids($data['categories']);
            }

            // Update images if provided
            if (isset($data['images'])) {
                $product->set_image_id($data['images']['featured'] ?? '');
                $product->set_gallery_image_ids($data['images']['gallery'] ?? array());
            }

            // Update attributes if provided
            if (isset($data['attributes'])) {
                $product->set_attributes($this->prepare_attributes($data['attributes']));
            }

            // Update meta data if provided
            if (isset($data['meta_data'])) {
                foreach ($data['meta_data'] as $meta) {
                    update_post_meta($product_id, $meta['key'], $meta['value']);
                }
            }

            // Save the product
            $product->save();

            return new WP_REST_Response(array(
                'success' => true,
                'message' => 'Product updated successfully',
                'product_id' => $product_id
            ), 200);

        } catch (Exception $e) {
            return new WP_Error('update_failed', 'Failed to update product: ' . $e->getMessage(), array('status' => 500));
        }
    }

    /**
     * Get product variations
     */
    public function get_product_variations($request) {
        $product_id = $request->get_param('id');

        $product = wc_get_product($product_id);

        if (!$product || !$product->is_type('variable')) {
            return new WP_Error('invalid_product', 'Product not found or not a variable product', array('status' => 404));
        }

        $variations = array();

        foreach ($product->get_children() as $variation_id) {
            $variation = wc_get_product($variation_id);
            if ($variation) {
                $variations[] = array(
                    'id' => $variation->get_id(),
                    'sku' => $variation->get_sku(),
                    'price' => $variation->get_price(),
                    'regular_price' => $variation->get_regular_price(),
                    'sale_price' => $variation->get_sale_price(),
                    'stock_quantity' => $variation->get_stock_quantity(),
                    'stock_status' => $variation->get_stock_status(),
                    'attributes' => $variation->get_variation_attributes(),
                    'image' => wp_get_attachment_image_url($variation->get_image_id(), 'full'),
                    'status' => $variation->get_status(),
                );
            }
        }

        return new WP_REST_Response($variations, 200);
    }

    /**
     * Bulk update products
     */
    public function bulk_update_products($request) {
        $data = $request->get_json_params();

        if (!isset($data['products']) || !is_array($data['products'])) {
            return new WP_Error('invalid_data', 'Products array is required', array('status' => 400));
        }

        $results = array();
        $updated = 0;
        $failed = 0;

        foreach ($data['products'] as $product_data) {
            try {
                $product_id = $product_data['id'];
                $updates = $product_data['updates'];

                $product = wc_get_product($product_id);
                if (!$product) {
                    $results[] = array('id' => $product_id, 'success' => false, 'error' => 'Product not found');
                    $failed++;
                    continue;
                }

                // Apply updates
                foreach ($updates as $field => $value) {
                    switch ($field) {
                        case 'price':
                            $product->set_price($value);
                            break;
                        case 'regular_price':
                            $product->set_regular_price($value);
                            break;
                        case 'sale_price':
                            $product->set_sale_price($value);
                            break;
                        case 'stock_quantity':
                            $product->set_stock_quantity($value);
                            break;
                        case 'stock_status':
                            $product->set_stock_status($value);
                            break;
                        case 'status':
                            $product->set_status($value);
                            break;
                        // Add more fields as needed
                    }
                }

                $product->save();
                $results[] = array('id' => $product_id, 'success' => true);
                $updated++;

            } catch (Exception $e) {
                $results[] = array('id' => $product_id, 'success' => false, 'error' => $e->getMessage());
                $failed++;
            }
        }

        return new WP_REST_Response(array(
            'success' => true,
            'updated' => $updated,
            'failed' => $failed,
            'results' => $results
        ), 200);
    }

    /**
     * Get capabilities
     */
    public function get_capabilities($request) {
        return new WP_REST_Response(array(
            'woocommerce_version' => WC()->version,
            'wordpress_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION,
            'features' => array(
                'variable_products' => true,
                'product_variations' => true,
                'bulk_operations' => true,
                'advanced_attributes' => true,
                'custom_meta' => true,
                'product_images' => true,
                'categories' => true,
                'shipping_classes' => true,
                'tax_classes' => true,
            ),
            'supported_actions' => array(
                'update_product',
                'bulk_update',
                'get_variations',
                'update_variation',
                'duplicate_product',
                'delete_product',
                'update_stock',
                'update_price',
            )
        ), 200);
    }

    /**
     * Execute admin action
     */
    public function execute_action($request) {
        $data = $request->get_json_params();

        if (!isset($data['action'])) {
            return new WP_Error('missing_action', 'Action parameter is required', array('status' => 400));
        }

        $action = $data['action'];
        $params = $data['params'] ?? array();

        try {
            switch ($action) {
                case 'regenerate_thumbnails':
                    $this->regenerate_thumbnails($params);
                    break;

                case 'clear_transients':
                    wc_delete_product_transients();
                    break;

                case 'update_product_lookup_tables':
                    wc_update_product_lookup_tables();
                    break;

                case 'recalculate_stock_levels':
                    $this->recalculate_stock_levels();
                    break;

                default:
                    return new WP_Error('unknown_action', 'Unknown action: ' . $action, array('status' => 400));
            }

            return new WP_REST_Response(array(
                'success' => true,
                'message' => 'Action executed successfully',
                'action' => $action
            ), 200);

        } catch (Exception $e) {
            return new WP_Error('action_failed', 'Action failed: ' . $e->getMessage(), array('status' => 500));
        }
    }

    /**
     * Helper: Get product categories
     */
    private function get_product_categories($product) {
        $categories = array();
        $terms = wp_get_post_terms($product->get_id(), 'product_cat');

        foreach ($terms as $term) {
            $categories[] = array(
                'id' => $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
            );
        }

        return $categories;
    }

    /**
     * Helper: Get product images
     */
    private function get_product_images($product) {
        return array(
            'featured' => $product->get_image_id(),
            'gallery' => $product->get_gallery_image_ids(),
            'featured_url' => wp_get_attachment_image_url($product->get_image_id(), 'full'),
            'gallery_urls' => array_map(function($id) {
                return wp_get_attachment_image_url($id, 'full');
            }, $product->get_gallery_image_ids())
        );
    }

    /**
     * Helper: Get product attributes
     */
    private function get_product_attributes($product) {
        $attributes = array();

        foreach ($product->get_attributes() as $attribute) {
            $attributes[] = array(
                'id' => $attribute->get_id(),
                'name' => $attribute->get_name(),
                'options' => $attribute->get_options(),
                'position' => $attribute->get_position(),
                'visible' => $attribute->get_visible(),
                'variation' => $attribute->get_variation(),
            );
        }

        return $attributes;
    }

    /**
     * Helper: Get product variations data
     */
    private function get_product_variations_data($product) {
        $variations = array();

        foreach ($product->get_children() as $variation_id) {
            $variation = wc_get_product($variation_id);
            if ($variation) {
                $variations[] = array(
                    'id' => $variation->get_id(),
                    'sku' => $variation->get_sku(),
                    'price' => $variation->get_price(),
                    'regular_price' => $variation->get_regular_price(),
                    'sale_price' => $variation->get_sale_price(),
                    'stock_quantity' => $variation->get_stock_quantity(),
                    'attributes' => $variation->get_variation_attributes(),
                );
            }
        }

        return $variations;
    }

    /**
     * Helper: Get product meta data
     */
    private function get_product_meta($product) {
        $meta_data = array();
        $meta = get_post_meta($product->get_id());

        foreach ($meta as $key => $values) {
            if (strpos($key, '_') === 0) continue; // Skip private meta

            $meta_data[] = array(
                'key' => $key,
                'value' => maybe_unserialize($values[0]),
            );
        }

        return $meta_data;
    }

    /**
     * Helper: Prepare attributes for saving
     */
    private function prepare_attributes($attributes_data) {
        $attributes = array();

        foreach ($attributes_data as $attr_data) {
            $attribute = new WC_Product_Attribute();
            $attribute->set_id($attr_data['id'] ?? 0);
            $attribute->set_name($attr_data['name']);
            $attribute->set_options($attr_data['options']);
            $attribute->set_position($attr_data['position'] ?? 0);
            $attribute->set_visible($attr_data['visible'] ?? true);
            $attribute->set_variation($attr_data['variation'] ?? false);

            $attributes[] = $attribute;
        }

        return $attributes;
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            'MWF Integration',
            'MWF Integration',
            'manage_options',
            'mwf-integration',
            array($this, 'admin_page'),
            'dashicons-rest-api',
            30
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('mwf_integration_settings', 'mwf_integration_api_key');

        add_settings_section(
            'mwf_integration_main',
            'API Configuration',
            array($this, 'settings_section_callback'),
            'mwf_integration_settings'
        );

        add_settings_field(
            'mwf_integration_api_key',
            'API Key',
            array($this, 'api_key_field_callback'),
            'mwf_integration_settings',
            'mwf_integration_main'
        );
    }

    /**
     * Admin page
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>MWF Integration API</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('mwf_integration_settings');
                do_settings_sections('mwf_integration_settings');
                submit_button();
                ?>
            </form>

            <hr>

            <h2>API Endpoints</h2>
            <p>The following REST API endpoints are available:</p>
            <ul>
                <li><code>GET /wp-json/mwf-integration/v1/products/{id}/edit</code> - Get enhanced product data</li>
                <li><code>PUT /wp-json/mwf-integration/v1/products/{id}</code> - Update product</li>
                <li><code>GET /wp-json/mwf-integration/v1/products/{id}/variations</code> - Get product variations</li>
                <li><code>POST /wp-json/mwf-integration/v1/products/bulk-update</code> - Bulk update products</li>
                <li><code>GET /wp-json/mwf-integration/v1/capabilities</code> - Get system capabilities</li>
                <li><code>POST /wp-json/mwf-integration/v1/actions</code> - Execute admin actions</li>
            </ul>

            <h2>Testing</h2>
            <p>Use the API key above to test endpoints. All requests require <code>Authorization: Bearer {api_key}</code> header.</p>
        </div>
        <?php
    }

    /**
     * Settings section callback
     */
    public function settings_section_callback() {
        echo '<p>Configure the MWF Integration API settings.</p>';
    }

    /**
     * API key field callback
     */
    public function api_key_field_callback() {
        $api_key = get_option('mwf_integration_api_key', '');
        echo '<input type="text" name="mwf_integration_api_key" value="' . esc_attr($api_key) . '" class="regular-text" />';
        echo '<p class="description">Generate a secure API key for authentication.</p>';
    }

    /**
     * Helper methods for actions
     */
    private function regenerate_thumbnails($params) {
        // Implement thumbnail regeneration
    }

    private function recalculate_stock_levels() {
        // Implement stock recalculation
        global $wpdb;

        $wpdb->query("
            UPDATE {$wpdb->postmeta} pm1
            INNER JOIN {$wpdb->postmeta} pm2 ON pm1.post_id = pm2.post_id
            SET pm1.meta_value = (
                SELECT SUM(CAST(pm3.meta_value AS SIGNED))
                FROM {$wpdb->postmeta} pm3
                WHERE pm3.post_id IN (
                    SELECT ID FROM {$wpdb->posts} WHERE post_parent = pm1.post_id AND post_type = 'product_variation'
                ) AND pm3.meta_key = '_stock'
            )
            WHERE pm1.meta_key = '_stock' AND pm2.meta_key = '_manage_stock' AND pm2.meta_value = 'yes'
        ");
    }
}

// Initialize the plugin
new MWF_Integration_API();

/**
 * Activation hook
 */
register_activation_hook(__FILE__, 'mwf_integration_activate');

function mwf_integration_activate() {
    // Generate initial API key if not exists
    if (!get_option('mwf_integration_api_key')) {
        $api_key = wp_generate_password(32, false);
        update_option('mwf_integration_api_key', $api_key);
    }

    // Flush rewrite rules
    flush_rewrite_rules();
}

/**
 * Deactivation hook
 */
register_deactivation_hook(__FILE__, 'mwf_integration_deactivate');

function mwf_integration_deactivate() {
    // Flush rewrite rules
    flush_rewrite_rules();
}