<?php
/**
 * MWF User Switching Class
 *
 * Provides secure user switching functionality for admin users to impersonate customers
 * Integrated with Laravel admin suite for seamless user management
 */

if (!defined('ABSPATH')) {
    exit;
}

class MWF_User_Switching {

    /**
     * Constructor
     */
    public function __construct() {
        error_log("MWF User Switching: Class instantiated");
        
        // Register auto-login handler IMMEDIATELY, not on 'init'
        // This is critical because the class is instantiated during 'init' action
        // so adding another 'init' hook here would be too late
        $this->handle_auto_login();
        
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        error_log("MWF User Switching: init_hooks() called");
        
        // AJAX endpoints for user switching
        add_action('wp_ajax_mwf_generate_switch_url', array($this, 'generate_switch_url'));
        // Removed nopriv access for security
        add_action('wp_ajax_mwf_admin_switch_user', array($this, 'handle_admin_switch_user'));
        add_action('wp_ajax_nopriv_mwf_admin_switch_user', array($this, 'handle_admin_switch_user'));
        add_action('wp_ajax_mwf_generate_plugin_switch_url', array($this, 'generate_plugin_switch_url'));
        add_action('wp_ajax_nopriv_mwf_generate_plugin_switch_url', array($this, 'generate_plugin_switch_url'));

        // Test endpoint
        add_action('wp_ajax_test_mwf', array($this, 'test_ajax'));
        add_action('wp_ajax_nopriv_test_mwf', array($this, 'test_ajax'));
    }

    /**
     * Generate secure admin switch key
     */
    private function get_admin_switch_key($user_id, $redirect_to) {
        $secret = 'mwf_admin_switch_2025_secret_key';
        return hash('sha256', $user_id . $redirect_to . $secret);
    }

    /**
     * Generate switch URL for admin use
     */
    public function generate_switch_url() {
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
        $admin_key = $this->get_admin_switch_key($user_id, $redirect_to);
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

    /**
     * Handle admin user switching
     */
    public function handle_admin_switch_user() {
        // Get parameters
        $user_id = intval($_GET['user_id'] ?? 0);
        $redirect_to = sanitize_text_field($_GET['redirect_to'] ?? '/my-account/');
        $admin_key = sanitize_text_field($_GET['admin_key'] ?? '');

        // Debug logging
        error_log("MWF User Switch Debug: user_id={$user_id}, redirect_to={$redirect_to}, admin_key={$admin_key}");

        // Verify admin key
        $expected_key = $this->get_admin_switch_key($user_id, $redirect_to);
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

    /**
     * Generate plugin switch URL with auto-login token
     */
    public function generate_plugin_switch_url() {
        // Add initial debug log
        error_log("MWF Plugin Switch: Function called with parameters: " . print_r($_GET, true));

        // Get parameters
        $user_id = intval($_GET['user_id'] ?? 0);
        $redirect_to = sanitize_text_field($_GET['redirect_to'] ?? '/my-account/');
        $admin_key = sanitize_text_field($_GET['admin_key'] ?? '');

        error_log("MWF Plugin Switch: Parsed - user_id: $user_id, redirect_to: $redirect_to, admin_key: $admin_key");

        // Verify admin key
        $expected_key = hash('sha256', $user_id . $redirect_to . 'mwf_admin_switch_2025_secret_key');
        error_log("MWF Plugin Switch: Expected key: $expected_key, Got key: $admin_key");

        if (!hash_equals($expected_key, $admin_key)) {
            error_log("MWF Plugin Switch: Invalid admin key");
            wp_die('Invalid admin key', 'Authentication Error', array('response' => 403));
        }

        // Validate user
        $user = get_userdata($user_id);
        if (!$user) {
            error_log("MWF Plugin Switch: User not found: {$user_id}");
            wp_die('User not found', 'User Error', array('response' => 404));
        }

        error_log("MWF Plugin Switch: Creating direct auto-login for user: {$user->user_login} (ID: {$user_id})");

        // Create a secure auto-login token
        $auto_login_token = wp_generate_password(32, false);
        $token_expiry = time() + 300; // 5 minutes

        // Store the token in database temporarily
        $token_data = array(
            'user_id' => $user_id,
            'redirect_to' => $redirect_to,
            'created' => time(),
            'expires' => $token_expiry
        );

        update_option("mwf_auto_login_token_{$auto_login_token}", $token_data, false);

        // Generate auto-login URL
        $auto_login_url = add_query_arg(array(
            'mwf_auto_login' => $auto_login_token
        ), home_url('/'));

        error_log("MWF Plugin Switch: Generated auto-login URL: {$auto_login_url}");

        // Return the URL as JSON instead of redirecting
        wp_send_json_success(array(
            'switch_url' => $auto_login_url,
            'user_name' => $user->display_name ?: $user->user_login,
            'message' => 'Auto-login URL generated successfully'
        ));
    }

    /**
     * Handle auto-login tokens
     */
    public function handle_auto_login() {
        // Add detailed logging
        error_log("MWF Auto Login: handle_auto_login() called. GET params: " . print_r($_GET, true));
        
        if (!isset($_GET['mwf_auto_login'])) {
            error_log("MWF Auto Login: No mwf_auto_login parameter found in request");
            return;
        }

        $token = sanitize_text_field($_GET['mwf_auto_login']);
        error_log("MWF Auto Login: Processing token: {$token}");
        
        $token_option = get_option("mwf_auto_login_token_{$token}");

        if (!$token_option || !is_array($token_option)) {
            error_log("MWF Auto Login: Invalid or expired token: {$token} - token_option: " . print_r($token_option, true));
            return;
        }

        // Check if token is expired
        if (time() > $token_option['expires']) {
            delete_option("mwf_auto_login_token_{$token}");
            error_log("MWF Auto Login: Token expired: {$token} - expires: {$token_option['expires']}, current time: " . time());
            return;
        }

        $user_id = intval($token_option['user_id']);
        $redirect_to = sanitize_text_field($token_option['redirect_to']);

        // Validate user
        $user = get_userdata($user_id);
        if (!$user) {
            error_log("MWF Auto Login: User not found: {$user_id}");
            delete_option("mwf_auto_login_token_{$token}");
            return;
        }

        error_log("MWF Auto Login: Processing auto-login for user: {$user->user_login} (ID: {$user_id})");

        // Clean up the token BEFORE setting cookies
        delete_option("mwf_auto_login_token_{$token}");

        // Perform the login - set all authentication
        wp_clear_auth_cookie();  // Clear any existing auth first
        wp_set_current_user($user_id, $user->user_login);
        wp_set_auth_cookie($user_id, true, is_ssl());
        do_action('wp_login', $user->user_login, $user);  // Fire login action

        // Set verification cookies
        setcookie('mwf_auto_logged_in', $user->user_login, time() + 3600, '/', '', is_ssl(), false);

        error_log("MWF Auto Login: User logged in successfully: {$user->user_login}");

        // Get redirect_to from query string if provided, otherwise use token data
        $final_redirect = isset($_GET['redirect_to']) ? sanitize_text_field($_GET['redirect_to']) : $redirect_to;
        
        // Remove the mwf_auto_login parameter and redirect
        $current_url = add_query_arg(array());  // Get current URL with all params
        $redirect_url = remove_query_arg('mwf_auto_login', $current_url);
        
        // If no other params remain and we have a redirect_to, use it
        if ($final_redirect && $final_redirect !== '/') {
            $redirect_url = home_url($final_redirect);
        }

        error_log("MWF Auto Login: Redirecting to: {$redirect_url}");

        wp_safe_redirect($redirect_url);
        exit;
    }

    /**
     * Test AJAX handler
     */
    public function test_ajax() {
        error_log('MWF Test AJAX: Handler called successfully');
        wp_send_json(['status' => 'success', 'message' => 'AJAX is working']);
    }
}