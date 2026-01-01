<?php
/**
 * Product Manager for Variable Subscription Products
 * 
 * Provides admin interface for managing and syncing subscription products
 * that use the variable-subscription product type.
 *
 * @package MWF_Subscriptions
 * @since 1.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Singleton class for managing subscription products
 */
class MWF_Product_Manager {
    
    /**
     * Singleton instance
     * @var MWF_Product_Manager
     */
    private static $instance = null;
    
    /**
     * Dynamically find subscription products
     * Products are identified by having _is_vegbox_subscription = 'yes'
     *
     * @return array Array of product IDs
     */
    public function get_subscription_product_ids() {
        $args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => '_is_vegbox_subscription',
                    'value' => 'yes',
                    'compare' => '='
                )
            ),
            'posts_per_page' => -1,
            'fields' => 'ids',
            'orderby' => 'menu_order',
            'order' => 'ASC'
        );

        $product_ids = get_posts($args);
        return $product_ids ?: array();
    }
    
    /**
     * Get singleton instance
     *
     * @return MWF_Product_Manager
     */
    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor - register hooks
     */
    private function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('wp_ajax_mwf_sync_subscription_product', [$this, 'ajax_sync_product']);
        add_action('wp_ajax_mwf_sync_all_subscription_products', [$this, 'ajax_sync_all']);
        add_action('wp_ajax_mwf_save_plan_id', [$this, 'ajax_save_plan_id']);
    }
    
    /**
     * Add admin menu page under WooCommerce
     */
    public function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            __('Subscription Products', 'mwf-subscriptions'),
            __('Subscription Products', 'mwf-subscriptions'),
            'manage_woocommerce',
            'mwf-subscription-products',
            [$this, 'render_admin_page']
        );
    }
    
    /**
     * Render the admin page
     */
    public function render_admin_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Subscription Product Management', 'mwf-subscriptions'); ?></h1>
            
            <p><?php echo esc_html__('Manage subscription products that have the "_is_vegbox_subscription" meta field set to "yes". Configure plan IDs and sync product variations.', 'mwf-subscriptions'); ?></p>
            
            <div class="mwf-admin-notice">
                <p><strong><?php echo esc_html__('How to add subscription products:', 'mwf-subscriptions'); ?></strong></p>
                <ol>
                    <li><?php echo esc_html__('Create or edit a WooCommerce product', 'mwf-subscriptions'); ?></li>
                    <li><?php echo esc_html__('Add custom field "_is_vegbox_subscription" with value "yes"', 'mwf-subscriptions'); ?></li>
                    <li><?php echo esc_html__('Set product type to "variable-subscription"', 'mwf-subscriptions'); ?></li>
                    <li><?php echo esc_html__('Add custom field "_vegbox_plan_id" with a unique plan ID', 'mwf-subscriptions'); ?></li>
                    <li><?php echo esc_html__('Create product variations for subscription options', 'mwf-subscriptions'); ?></li>
                </ol>
            </div>
            
            <div class="mwf-product-manager">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php echo esc_html__('Product ID', 'mwf-subscriptions'); ?></th>
                            <th><?php echo esc_html__('Product Name', 'mwf-subscriptions'); ?></th>
                            <th><?php echo esc_html__('Plan ID', 'mwf-subscriptions'); ?></th>
                            <th><?php echo esc_html__('Type', 'mwf-subscriptions'); ?></th>
                            <th><?php echo esc_html__('Variations', 'mwf-subscriptions'); ?></th>
                            <th><?php echo esc_html__('Actions', 'mwf-subscriptions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $subscription_product_ids = $this->get_subscription_product_ids();
                        foreach ($subscription_product_ids as $product_id):
                        ?>
                            <?php
                            $product = wc_get_product($product_id);
                            if (!$product) {
                                continue;
                            }
                            
                            $variation_count = 0;
                            if ($product->is_type('variable') || $product->is_type('variable-subscription')) {
                                $variations = $product->get_children();
                                $variation_count = count($variations);
                            }
                            ?>
                            <tr data-product-id="<?php echo esc_attr($product_id); ?>">
                                <td><?php echo esc_html($product_id); ?></td>
                                <td>
                                    <strong>
                                        <a href="<?php echo esc_url(get_edit_post_link($product_id)); ?>" target="_blank">
                                            <?php echo esc_html($product->get_name()); ?>
                                        </a>
                                    </strong>
                                </td>
                                <td>
                                    <?php
                                    $plan_id = get_post_meta($product_id, '_vegbox_plan_id', true);
                                    ?>
                                    <input type="number" 
                                           class="mwf-plan-id-input" 
                                           data-product-id="<?php echo esc_attr($product_id); ?>" 
                                           value="<?php echo esc_attr($plan_id); ?>" 
                                           placeholder="<?php echo esc_attr__('Enter plan ID', 'mwf-subscriptions'); ?>"
                                           min="1" 
                                           style="width: 80px;">
                                    <button type="button" class="button button-small mwf-save-plan-id" data-product-id="<?php echo esc_attr($product_id); ?>">
                                        <?php echo esc_html__('Save', 'mwf-subscriptions'); ?>
                                    </button>
                                </td>
                                <td>
                                    <span class="product-type-badge">
                                        <?php echo esc_html($product->get_type()); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="variation-count"><?php echo esc_html($variation_count); ?></span>
                                    <?php if ($variation_count !== 6): ?>
                                        <span class="dashicons dashicons-warning" style="color: #f56e28;" title="<?php echo esc_attr__('Expected 6 variations', 'mwf-subscriptions'); ?>"></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button type="button" class="button mwf-sync-product" data-product-id="<?php echo esc_attr($product_id); ?>">
                                        <?php echo esc_html__('Sync', 'mwf-subscriptions'); ?>
                                    </button>
                                    <span class="spinner" style="float: none; margin: 0 0 0 10px;"></span>
                                    <span class="sync-result"></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <p class="submit">
                    <button type="button" id="mwf-sync-all-products" class="button button-primary">
                        <?php echo esc_html__('Sync All Products', 'mwf-subscriptions'); ?>
                    </button>
                    <span class="spinner" style="float: none; margin: 0 0 0 10px;"></span>
                </p>
            </div>
        </div>
        
        <style>
            .mwf-product-manager {
                background: #fff;
                padding: 20px;
                margin-top: 20px;
            }
            .mwf-admin-notice {
                background: #fff3cd;
                border: 1px solid #ffeaa7;
                border-radius: 4px;
                padding: 15px;
                margin: 20px 0;
            }
            .mwf-admin-notice ol {
                margin: 10px 0 0 20px;
            }
            .mwf-admin-notice li {
                margin: 5px 0;
            }
            .product-type-badge {
                background: #2271b1;
                color: #fff;
                padding: 3px 8px;
                border-radius: 3px;
                font-size: 12px;
                font-weight: 600;
            }
            .sync-result {
                margin-left: 10px;
                font-weight: 600;
            }
            .sync-result.success {
                color: #46b450;
            }
            .sync-result.error {
                color: #dc3232;
            }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Single product sync
            $('.mwf-sync-product').on('click', function() {
                var $button = $(this);
                var $row = $button.closest('tr');
                var productId = $button.data('product-id');
                var $spinner = $row.find('.spinner');
                var $result = $row.find('.sync-result');
                
                $button.prop('disabled', true);
                $spinner.addClass('is-active');
                $result.text('').removeClass('success error');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'mwf_sync_subscription_product',
                        product_id: productId,
                        nonce: '<?php echo wp_create_nonce('mwf_sync_products'); ?>'
                    },
                    success: function(response) {
                        $spinner.removeClass('is-active');
                        $button.prop('disabled', false);
                        
                        if (response.success) {
                            $result.addClass('success').text(response.data.message);
                            // Update variation count
                            if (response.data.variation_count !== undefined) {
                                $row.find('.variation-count').text(response.data.variation_count);
                            }
                            
                            setTimeout(function() {
                                $result.fadeOut(function() {
                                    $(this).text('').show().removeClass('success');
                                });
                            }, 3000);
                        } else {
                            $result.addClass('error').text(response.data || 'Sync failed');
                        }
                    },
                    error: function() {
                        $spinner.removeClass('is-active');
                        $button.prop('disabled', false);
                        $result.addClass('error').text('AJAX error');
                    }
                });
            });
            
            // Sync all products
            $('#mwf-sync-all-products').on('click', function() {
                var $button = $(this);
                var $spinner = $button.next('.spinner');
                
                if (!confirm('<?php echo esc_js(__('This will sync all subscription products. Continue?', 'mwf-subscriptions')); ?>')) {
                    return;
                }
                
                $button.prop('disabled', true);
                $spinner.addClass('is-active');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'mwf_sync_all_subscription_products',
                        nonce: '<?php echo wp_create_nonce('mwf_sync_products'); ?>'
                    },
                    success: function(response) {
                        $spinner.removeClass('is-active');
                        $button.prop('disabled', false);
                        
                        if (response.success) {
                            alert(response.data.message);
                            location.reload();
                        } else {
                            alert('Sync failed: ' + (response.data || 'Unknown error'));
                        }
                    },
                    error: function() {
                        $spinner.removeClass('is-active');
                        $button.prop('disabled', false);
                        alert('AJAX error occurred');
                    }
                });
            });
            
            // Save plan ID
            $('.mwf-save-plan-id').on('click', function() {
                var $button = $(this);
                var $input = $button.prev('.mwf-plan-id-input');
                var productId = $button.data('product-id');
                var planId = $input.val().trim();
                
                $button.prop('disabled', true);
                $button.text('<?php echo esc_js(__('Saving...', 'mwf-subscriptions')); ?>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'mwf_save_plan_id',
                        product_id: productId,
                        plan_id: planId,
                        nonce: '<?php echo wp_create_nonce('mwf_save_plan_id'); ?>'
                    },
                    success: function(response) {
                        $button.prop('disabled', false);
                        $button.text('<?php echo esc_js(__('Save', 'mwf-subscriptions')); ?>');
                        
                        if (response.success) {
                            // Show success indication
                            $button.css('background-color', '#46b450').text('<?php echo esc_js(__('Saved', 'mwf-subscriptions')); ?>');
                            setTimeout(function() {
                                $button.css('background-color', '').text('<?php echo esc_js(__('Save', 'mwf-subscriptions')); ?>');
                            }, 2000);
                        } else {
                            alert('<?php echo esc_js(__('Error saving plan ID:', 'mwf-subscriptions')); ?> ' + (response.data || 'Unknown error'));
                        }
                    },
                    error: function() {
                        $button.prop('disabled', false);
                        $button.text('<?php echo esc_js(__('Save', 'mwf-subscriptions')); ?>');
                        alert('<?php echo esc_js(__('AJAX error occurred', 'mwf-subscriptions')); ?>');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * AJAX handler for syncing a single product
     */
    public function ajax_sync_product() {
        check_ajax_referer('mwf_sync_products', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Permission denied');
            return;
        }
        
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        
        $subscription_product_ids = $this->get_subscription_product_ids();
        if (!$product_id || !in_array($product_id, $subscription_product_ids)) {
            wp_send_json_error('Invalid product ID');
            return;
        }
        
        $result = $this->sync_product($product_id);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * AJAX handler for syncing all products
     */
    public function ajax_sync_all() {
        check_ajax_referer('mwf_sync_products', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Permission denied');
            return;
        }
        
        $synced = 0;
        $errors = [];
        
        $subscription_product_ids = $this->get_subscription_product_ids();
        foreach ($subscription_product_ids as $product_id) {
            $result = $this->sync_product($product_id);
            if ($result['success']) {
                $synced++;
            } else {
                $errors[] = "Product {$product_id}: " . $result['message'];
            }
        }
        
        if (empty($errors)) {
            wp_send_json_success([
                'message' => sprintf(__('Successfully synced %d products', 'mwf-subscriptions'), $synced)
            ]);
        } else {
            wp_send_json_error(implode("\n", $errors));
        }
    }
    
    /**
     * AJAX handler for saving plan ID
     */
    public function ajax_save_plan_id() {
        check_ajax_referer('mwf_save_plan_id', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Permission denied');
            return;
        }
        
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        $plan_id = isset($_POST['plan_id']) ? intval($_POST['plan_id']) : 0;
        
        if (!$product_id) {
            wp_send_json_error('Invalid product ID');
            return;
        }
        
        // Verify this is a subscription product
        $subscription_product_ids = $this->get_subscription_product_ids();
        if (!in_array($product_id, $subscription_product_ids)) {
            wp_send_json_error('Product is not a subscription product');
            return;
        }
        
        // Save or delete the plan ID
        if ($plan_id > 0) {
            update_post_meta($product_id, '_vegbox_plan_id', $plan_id);
        } else {
            delete_post_meta($product_id, '_vegbox_plan_id');
        }
        
        wp_send_json_success([
            'message' => __('Plan ID saved successfully', 'mwf-subscriptions')
        ]);
    }
    
    /**
     * Sync a single product's variations
     *
     * @param int $product_id Product ID to sync
     * @return array Result with success status and message
     */
    private function sync_product($product_id) {
        $product = wc_get_product($product_id);
        
        if (!$product) {
            return [
                'success' => false,
                'message' => __('Product not found', 'mwf-subscriptions')
            ];
        }
        
        // Get all published variations
        $children = get_posts([
            'post_parent' => $product_id,
            'post_type' => 'product_variation',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'fields' => 'ids'
        ]);
        
        // Update _children meta
        update_post_meta($product_id, '_children', $children);
        
        // Clear product cache
        wc_delete_product_transients($product_id);
        wp_cache_delete('product-' . $product_id, 'products');
        
        // Run WooCommerce's own sync if available
        if (function_exists('wc_get_container')) {
            try {
                $data_store = $product->get_data_store();
                if (method_exists($data_store, 'sync_variation_data')) {
                    $data_store->sync_variation_data($product_id);
                }
            } catch (Exception $e) {
                error_log('[MWF Product Manager] Sync error: ' . $e->getMessage());
            }
        }
        
        return [
            'success' => true,
            'message' => sprintf(__('Synced %d variations', 'mwf-subscriptions'), count($children)),
            'variation_count' => count($children)
        ];
    }
}
