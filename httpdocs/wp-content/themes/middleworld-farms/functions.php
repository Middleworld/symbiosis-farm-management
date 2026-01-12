<?php
/**
 * Middleworld Farms Child Theme Functions
 * 
 * This theme fetches branding (colors, logos, fonts) from the Laravel admin panel
 * via API and applies them to WordPress.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Enqueue parent and child theme styles
 */
function middleworld_farms_enqueue_styles() {
    // Parent theme stylesheet
    wp_enqueue_style('parent-style', get_template_directory_uri() . '/style.css');

    // Child theme stylesheet
    wp_enqueue_style('child-style',
        get_stylesheet_directory_uri() . '/style.css',
        array('parent-style'),
        wp_get_theme()->get('Version')
    );

    // Swiper carousel styles
    wp_enqueue_style('swiper-css',
        'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css',
        array(),
        '11.0.0'
    );
}
add_action('wp_enqueue_scripts', 'middleworld_farms_enqueue_styles');

/**
 * Enqueue scripts
 */
function middleworld_farms_enqueue_scripts() {
    // Swiper carousel script
    wp_enqueue_script('swiper-js',
        'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js',
        array(),
        '11.0.0',
        true
    );

    // Custom carousel initialization
    wp_enqueue_script('vegbox-carousel',
        get_stylesheet_directory_uri() . '/js/vegbox-carousel.js',
        array('swiper-js'),
        wp_get_theme()->get('Version'),
        true
    );
    
    // WooCommerce variation enhancement (only on product pages)
    if (is_product()) {
        wp_enqueue_style('woocommerce-variations',
            get_stylesheet_directory_uri() . '/css/woocommerce-variations.css',
            array(),
            wp_get_theme()->get('Version')
        );
        
        wp_enqueue_script('woocommerce-variations-js',
            get_stylesheet_directory_uri() . '/js/woocommerce-variations.js',
            array('jquery', 'wc-add-to-cart-variation'),
            wp_get_theme()->get('Version'),
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'middleworld_farms_enqueue_scripts');

/**
 * Fetch branding data from Laravel API
 * 
 * @return array|null Branding data or null if unavailable
 */
function mwf_get_branding() {
    // Cache branding for 1 hour
    $branding = get_transient('mwf_branding_data');
    
    if (false === $branding) {
        // Fetch from Laravel API
        $api_url = 'https://admin.soilsync.shop/api/branding';
        
        $response = wp_remote_get($api_url, array(
            'timeout' => 10,
            'headers' => array(
                'Accept' => 'application/json',
            ),
        ));
        
        if (is_wp_error($response)) {
            error_log('MWF Branding API Error: ' . $response->get_error_message());
            return null;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!empty($data['success']) && !empty($data['data'])) {
            $branding = $data['data'];
            // Cache for 1 hour
            set_transient('mwf_branding_data', $branding, HOUR_IN_SECONDS);
        } else {
            return null;
        }
    }
    
    return $branding;
}

/**
 * Inject CSS variables into <head>
 */
function mwf_inject_css_variables() {
    $branding = mwf_get_branding();
    
    if (!$branding) {
        return;
    }
    
    $colors = $branding['colors'] ?? [];
    $fonts = $branding['fonts'] ?? [];
    
    ?>
    <style id="mwf-branding-variables">
        :root {
            --mwf-primary: <?php echo esc_attr($colors['primary'] ?? '#2d5016'); ?>;
            --mwf-secondary: <?php echo esc_attr($colors['secondary'] ?? '#5a7c3e'); ?>;
            --mwf-accent: <?php echo esc_attr($colors['accent'] ?? '#f5c518'); ?>;
            --mwf-text: <?php echo esc_attr($colors['text'] ?? '#1a1a1a'); ?>;
            --mwf-background: <?php echo esc_attr($colors['background'] ?? '#ffffff'); ?>;
            --mwf-font-heading: <?php echo esc_attr($fonts['heading'] ?? 'Inter, system-ui, sans-serif'); ?>;
            --mwf-font-body: <?php echo esc_attr($fonts['body'] ?? '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'); ?>;
        }
        
        /* Apply branding colors */
        body {
            color: var(--mwf-text);
            background-color: var(--mwf-background);
            font-family: var(--mwf-font-body);
        }
        
        h1, h2, h3, h4, h5, h6 {
            font-family: var(--mwf-font-heading);
            color: var(--mwf-primary);
        }
        
        a {
            color: var(--mwf-primary);
        }
        
        a:hover {
            color: var(--mwf-secondary);
        }
        
        .button, .wp-block-button__link {
            background-color: var(--mwf-accent);
            color: var(--mwf-text);
        }
        
        .button:hover, .wp-block-button__link:hover {
            background-color: var(--mwf-primary);
            color: var(--mwf-background);
        }
    </style>
    <?php
}
add_action('wp_head', 'mwf_inject_css_variables', 1);

/**
 * Replace site logo with branding logo from Laravel
 */
function mwf_custom_logo() {
    $branding = mwf_get_branding();
    
    if (!$branding || empty($branding['logos']['main'])) {
        return;
    }
    
    $logo_url = $branding['logos']['main'];
    $logo_alt = $branding['logos']['alt_text'] ?? get_bloginfo('name');
    
    ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Find and replace WordPress logo with branding logo
            const logoElements = document.querySelectorAll('.site-logo img, .custom-logo');
            logoElements.forEach(function(img) {
                img.src = '<?php echo esc_url($logo_url); ?>';
                img.alt = '<?php echo esc_attr($logo_alt); ?>';
            });
        });
    </script>
    <?php
}
add_action('wp_footer', 'mwf_custom_logo');

/**
 * Add custom body class
 */
function mwf_body_classes($classes) {
    $classes[] = 'middleworld-farms-theme';
    return $classes;
}
add_filter('body_class', 'mwf_body_classes');

/**
 * Clear branding cache (can be called via cron or webhook)
 */
function mwf_clear_branding_cache() {
    delete_transient('mwf_branding_data');
}

/**
 * Admin notice if branding API is unavailable
 */
function mwf_admin_notices() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    $branding = mwf_get_branding();
    
    if (null === $branding) {
        ?>
        <div class="notice notice-warning is-dismissible">
            <p><strong>Middleworld Farms Theme:</strong> Unable to fetch branding from Laravel admin. Using default colors.</p>
        </div>
        <?php
    }
}
add_action('admin_notices', 'mwf_admin_notices');

// ====================================================================================
// PERFORMANCE: LOAD GOOGLE MAPS ASYNC
// ====================================================================================
/**
 * Add async attribute to Google Maps scripts to prevent render blocking
 */
add_filter('script_loader_tag', 'mwf_async_google_maps', 10, 3);
function mwf_async_google_maps($tag, $handle, $src) {
    // Add async to Google Maps API scripts
    if (strpos($handle, 'google-maps') !== false || strpos($src, 'maps.googleapis.com') !== false) {
        $tag = str_replace(' src', ' async defer src', $tag);
    }
    
    return $tag;
}
// ====================================================================================
// EMAIL VALIDATION SYSTEM - PREVENT TYPOS AT CHECKOUT
// ====================================================================================
/**
 * Enqueue email validation script on checkout and account pages
 */
add_action('wp_enqueue_scripts', 'mwf_enqueue_email_validation');
function mwf_enqueue_email_validation() {
    // Only load on checkout, account, and registration pages
    if ((function_exists('is_checkout') && is_checkout()) || (function_exists('is_account_page') && is_account_page()) || is_page('register')) {
        wp_enqueue_script(
            'mwf-email-validation',
            get_stylesheet_directory_uri() . '/assets/js/email-validation.js',
            array('jquery'),
            '1.0.0',
            true
        );
    }
}

/**
 * Server-side email validation for extra security
 */
add_action('woocommerce_checkout_process', 'mwf_validate_checkout_email');
function mwf_validate_checkout_email() {
    $email = sanitize_email($_POST['billing_email']);
    
    if (empty($email)) {
        wc_add_notice(__('Email address is required.'), 'error');
        return;
    }
    
    // Check for common typos
    $typo_corrections = array(
        'hotmai.co.uk' => 'hotmail.co.uk',
        'hotmial.co.uk' => 'hotmail.co.uk',
        'hotmail.co.k' => 'hotmail.co.uk',
        'gmai.com' => 'gmail.com',
        'gmial.com' => 'gmail.com',
        'gmail.co.uk' => 'gmail.com',
        'yahoo.co.k' => 'yahoo.co.uk'
    );
    
    foreach ($typo_corrections as $typo => $correction) {
        if (strpos($email, $typo) !== false) {
            $suggested = str_replace($typo, $correction, $email);
            wc_add_notice(
                sprintf(__('Did you mean %s? Please check your email address.'), $suggested),
                'error'
            );
            return;
        }
    }
    
    // Check for suspicious patterns
    if (preg_match('/\.\.|\s|@\.|\.\@|@.*@|^\.|\.$|@$|^@/', $email)) {
        wc_add_notice(__('Please check your email address format.'), 'error');
        return;
    }
}

/**
 * Validate email during user registration
 */
add_action('woocommerce_register_post', 'mwf_validate_registration_email', 10, 3);
function mwf_validate_registration_email($username, $email, $errors) {
    // Same validation as checkout
    if (!empty($email)) {
        $typo_corrections = array(
            'hotmai.co.uk' => 'hotmail.co.uk',
            'hotmial.co.uk' => 'hotmail.co.uk',
            'hotmail.co.k' => 'hotmail.co.uk',
            'gmai.com' => 'gmail.com',
            'gmial.com' => 'gmail.com',
            'gmail.co.uk' => 'gmail.com'
        );
        
        foreach ($typo_corrections as $typo => $correction) {
            if (strpos($email, $typo) !== false) {
                $suggested = str_replace($typo, $correction, $email);
                $errors->add(
                    'registration-error-invalid-email',
                    sprintf(__('Did you mean %s? Please check your email address.'), $suggested)
                );
                break;
            }
        }
    }
}

/**
 * Log email validation events for monitoring
 */
add_action('wp_ajax_mwf_log_email_correction', 'mwf_log_email_correction');
add_action('wp_ajax_nopriv_mwf_log_email_correction', 'mwf_log_email_correction');
function mwf_log_email_correction() {
    $original = sanitize_email($_POST['original']);
    $corrected = sanitize_email($_POST['corrected']);
    
    if ($original && $corrected) {
        error_log("MWF Email Correction: {$original} → {$corrected}");
        
        // Optionally store in database for analytics
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'mwf_email_corrections',
            array(
                'original_email' => $original,
                'corrected_email' => $corrected,
                'correction_date' => current_time('mysql'),
                'user_ip' => $_SERVER['REMOTE_ADDR']
            ),
            array('%s', '%s', '%s', '%s')
        );
    }
    
    wp_die();
}

/**
 * Create table for email correction analytics
 */
register_activation_hook(__FILE__, 'mwf_create_email_corrections_table');
function mwf_create_email_corrections_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'mwf_email_corrections';
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        original_email varchar(255) NOT NULL,
        corrected_email varchar(255) NOT NULL,
        correction_date datetime DEFAULT CURRENT_TIMESTAMP,
        user_ip varchar(45),
        PRIMARY KEY (id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Debugging for translation loading
if (defined('MWF_DEBUG') && constant('MWF_DEBUG')) {
    add_filter('load_textdomain', function ($override, $domain, $mofile = null) {
        if ($domain === 'woocommerce' || $domain === 'advanced-coupons-for-woocommerce-free') {
            error_log("Translation loaded too early for: " . $domain);
            error_log(print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10), true));
        }
        return $override;
    }, 10, 3);
}

// Change WooCommerce subscription sign-up fee text
function change_signup_fee_text($translated_text, $text, $domain) {
    if ($domain == 'woocommerce-subscriptions' && $text == 'Sign-up fee') {
        return 'Deposit';
    }
    return $translated_text;
}
add_filter('gettext', 'change_signup_fee_text', 20, 3);

// Prevent multiple Google Maps API calls
add_action('wp_enqueue_scripts', function () {
    wp_dequeue_script('address-field-autocomplete-for-woocommerce');
}, 20);

// ====================================================================================
// COLLECTION DAY PREFERENCE - MY ACCOUNT PAGE
// ====================================================================================
/**
 * Add preferred collection day field to My Account page - ONLY for collection customers
 */
add_action('woocommerce_edit_account_form', 'mwf_add_collection_day_field');
function mwf_add_collection_day_field() {
    $user_id = get_current_user_id();
    
    // Check if this customer has any COLLECTION subscriptions (shipping = 0.00)
    $has_collection_subscription = false;
    
    if (function_exists('wcs_get_users_subscriptions')) {
        $subscriptions = wcs_get_users_subscriptions($user_id);
        
        foreach ($subscriptions as $subscription) {
            // Check if subscription has zero shipping (indicates collection)
            $shipping_total = $subscription->get_shipping_total();
            
            if ($shipping_total == 0 || $shipping_total == '0.00') {
                $has_collection_subscription = true;
                break;
            }
        }
    }
    
    // Only show the field if customer has collection subscriptions
    if (!$has_collection_subscription) {
        return;
    }
    
    $current_day = get_user_meta($user_id, 'preferred_collection_day', true);
    if (empty($current_day)) {
        $current_day = 'Friday'; // Default to Friday
    }
    
    ?>
    <fieldset class="mwf-collection-day-fieldset">
        <legend><?php esc_html_e('Collection Preferences', 'woocommerce'); ?></legend>
        
        <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
            <label for="preferred_collection_day"><?php esc_html_e('Preferred Collection Day', 'woocommerce'); ?> <span class="required">*</span></label>
            <select name="preferred_collection_day" id="preferred_collection_day" class="woocommerce-Input woocommerce-Input--text input-text" required>
                <option value="Friday" <?php selected($current_day, 'Friday'); ?>>Friday</option>
                <option value="Saturday" <?php selected($current_day, 'Saturday'); ?>>Saturday</option>
            </select>
            <em style="font-size: 0.9em; color: #666;">Choose which day you'd like to collect your vegetable box each week.</em>
        </p>
    </fieldset>
    
    <style>
        .mwf-collection-day-fieldset {
            border: 1px solid #ddd;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
            background: #f9f9f9;
        }
        .mwf-collection-day-fieldset legend {
            font-weight: bold;
            font-size: 1.1em;
            padding: 0 10px;
            color: #333;
        }
    </style>
    <?php
}

/**
 * Validate the collection day field - must be Friday or Saturday
 */
add_action('woocommerce_save_account_details_errors', 'mwf_validate_collection_day_field', 10, 1);
function mwf_validate_collection_day_field($args) {
    $user_id = get_current_user_id();
    
    // Check if this customer has collection subscriptions
    $has_collection_subscription = false;
    
    if (function_exists('wcs_get_users_subscriptions')) {
        $subscriptions = wcs_get_users_subscriptions($user_id);
        
        foreach ($subscriptions as $subscription) {
            $shipping_total = $subscription->get_shipping_total();
            
            if ($shipping_total == 0 || $shipping_total == '0.00') {
                $has_collection_subscription = true;
                break;
            }
        }
    }
    
    // Only validate if customer has collection subscriptions
    if (!$has_collection_subscription) {
        return;
    }
    
    if (isset($_POST['preferred_collection_day'])) {
        $preferred_day = sanitize_text_field($_POST['preferred_collection_day']);
        
        // Must be Friday or Saturday
        if (!in_array($preferred_day, ['Friday', 'Saturday'])) {
            $args->add('error', __('Please select either Friday or Saturday as your collection day.', 'woocommerce'));
        }
    } else {
        // Required field
        $args->add('error', __('Please select your preferred collection day.', 'woocommerce'));
    }
}

/**
 * Save the collection day preference to user meta
 */
add_action('woocommerce_save_account_details', 'mwf_save_collection_day_field', 10, 1);
function mwf_save_collection_day_field($user_id) {
    if (isset($_POST['preferred_collection_day'])) {
        $preferred_day = sanitize_text_field($_POST['preferred_collection_day']);
        
        // Only allow Friday or Saturday
        if (in_array($preferred_day, ['Friday', 'Saturday'])) {
            update_user_meta($user_id, 'preferred_collection_day', $preferred_day);
            
            // Log the change for debugging
            error_log("MWF: Updated collection day preference for user {$user_id} to {$preferred_day}");
        }
    }
}

// ====================================================================================
// DISABLE ALL WOO COMMERCE EMAIL NOTIFICATIONS
// All emailing will be handled in Laravel admin from now on
// ====================================================================================

add_action('woocommerce_init', 'mwf_disable_woocommerce_emails');
function mwf_disable_woocommerce_emails() {
    // Disable all WooCommerce email notifications
    $emails = WC()->mailer()->get_emails();
    foreach ($emails as $email) {
        $email->enabled = 'no';
    }
}


// ====================================================================================
// DISABLE EXPRESS CHECKOUT FOR SUBSCRIPTIONS & PAY-WHAT-YOU-CAN PRODUCTS
// ====================================================================================
/**
 * Disable Apple Pay, Google Pay, and Stripe Express for subscription products
 * and Name Your Price products (requires checkout form for proper configuration)
 */
add_filter('woocommerce_product_supports', 'mwf_remove_express_checkout_for_subscriptions', 10, 3);
function mwf_remove_express_checkout_for_subscriptions($supports, $feature, $product) {
    if (in_array($feature, ['stripe_checkout', 'express_checkout', 'apple_pay'], true)) {
        // Check for WooCommerce Name Your Price plugin
        if (function_exists('wc_nyp_is_nyp') && call_user_func('wc_nyp_is_nyp', $product)) {
            return false;
        }
        // Disable for subscription products
        if ($product->is_type('subscription') || $product->is_type('variable-subscription')) {
            return false;
        }
    }
    return $supports;
}
function mwf_get_admin_switch_key($user_id, $redirect_to) {
    $secret = 'mwf_admin_switch_2025_secret_key';
    return hash('sha256', $user_id . $redirect_to . $secret);
}

// Add admin endpoint to generate switch URLs
add_action('wp_ajax_mwf_generate_switch_url', 'mwf_generate_switch_url');

function mwf_generate_switch_url() {
    // Only allow admin access
    if (!current_user_can('manage_options')) {
        wp_die('Insufficient permissions', 'Permission Error', array('response' => 403));
    }

    $user_id = intval($_GET['user_id'] ?? $_POST['user_id'] ?? 0);
    $redirect_to = sanitize_text_field($_GET['redirect_to'] ?? $_POST['redirect_to'] ?? '/my-account/');

    if (empty($user_id)) {
        wp_send_json_error('User ID is required');
        return;
    }

    // Verify user exists
    $user = get_user_by('ID', $user_id);
    if (!$user) {
        wp_send_json_error('User not found');
        return;
    }
    
    // Generate the switch URL
    $admin_key = mwf_get_admin_switch_key($user_id, $redirect_to);
    $switch_url = add_query_arg(array(
        'action' => 'mwf_admin_switch_user',
        'user_id' => $user_id,
        'redirect_to' => $redirect_to,
        'admin_key' => $admin_key
    ), admin_url('admin-ajax.php'));

    wp_send_json_success(array(
        'switch_url' => $switch_url,
        'user_name' => $user->display_name ?: $user->user_login,
        'redirect_to' => $redirect_to
    ));
}

// Handle admin user switching via AJAX
add_action('wp_ajax_mwf_admin_switch_user', 'mwf_handle_admin_switch_user');
add_action('wp_ajax_nopriv_mwf_admin_switch_user', 'mwf_handle_admin_switch_user');

function mwf_handle_admin_switch_user() {
    // Get parameters
    $user_id = intval($_GET['user_id'] ?? 0);
    $redirect_to = sanitize_text_field($_GET['redirect_to'] ?? '/my-account/');
    $admin_key = sanitize_text_field($_GET['admin_key'] ?? '');
    
    // Debug logging
    error_log("MWF User Switch Debug: user_id={$user_id}, redirect_to={$redirect_to}, admin_key={$admin_key}");
    
    // Verify admin key
    $expected_key = mwf_get_admin_switch_key($user_id, $redirect_to);
    if (!hash_equals($expected_key, $admin_key)) {
        error_log("MWF User Switch: Invalid admin key. Expected: {$expected_key}, Got: {$admin_key}");
        wp_die('Invalid admin key', 'Authentication Error', array('response' => 403));
    }
    
    // Validate user
    $user = get_userdata($user_id);
    if (!$user) {
        error_log("MWF User Switch: User not found: {$user_id}");
        wp_die('User not found', 'User Error', array('response' => 404));
    }
    
    error_log("MWF User Switch: Attempting to switch to user: {$user->user_login} (ID: {$user_id})");
    
    // Method 1: Completely destroy current session
    wp_destroy_current_session();
    wp_clear_auth_cookie();
    
    // Method 2: Force new user login with extended session
    wp_set_current_user($user_id, $user->user_login);
    
    // Method 3: Set auth cookie with remember me and extended time
    $remember = true;
    $secure = is_ssl();
    $expiration = time() + (14 * DAY_IN_SECONDS); // 2 weeks
    
    wp_set_auth_cookie($user_id, $remember, $secure, $expiration);
    
    error_log("MWF User Switch: Auth cookie set for user: {$user->user_login} with expiration: " . date('Y-m-d H:i:s', $expiration));
    
    // Method 4: Set additional verification cookies
    setcookie('mwf_switched_user', $user->user_login, time() + 3600, '/', '', $secure, false);
    setcookie('mwf_switch_timestamp', time(), time() + 3600, '/', '', $secure, false);
    
    // Method 5: Force session regeneration
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
    
    error_log("MWF User Switch: All authentication methods applied for user: {$user->user_login}");
    
    error_log("MWF User Switch: Switch completed, redirecting to: {$redirect_to}");
    
    // Redirect with cache busting parameters
    $redirect_url = add_query_arg([
        'switched' => 1,
        'user' => $user->user_login,
        '_t' => time(),
        'mwf_switch' => 'success'
    ], home_url($redirect_to));
    
    error_log("MWF User Switch: Final redirect URL: {$redirect_url}");
    
    wp_redirect($redirect_url);
    exit;
}
