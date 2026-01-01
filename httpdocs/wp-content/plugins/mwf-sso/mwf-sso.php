<?php
/**
 * Plugin Name: MWF SSO Integration
 * Description: Single Sign-On integration with Laravel Admin using OAuth2
 * Version: 1.0.0
 * Author: Middle World Farms
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class MWF_SSO {
    private $client_id;
    private $client_secret;
    private $admin_url;

    public function __construct() {
        $this->client_id = get_option('mwf_sso_client_id', '');
        $this->client_secret = get_option('mwf_sso_client_secret', '');
        $this->admin_url = get_option('mwf_sso_admin_url', 'https://admin.soilsync.shop');

        // CRITICAL: Never add SSO hooks during any logout process
        if (isset($_GET['action']) && $_GET['action'] === 'logout') {
            return;
        }

        add_action('wp', array($this, 'maybe_redirect_to_sso'), 99);
        add_action('login_init', array($this, 'maybe_redirect_to_sso'), 99);
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_filter('login_url', array($this, 'custom_login_url'), 10, 3);
        add_action('wp_ajax_mwf_sso_callback', array($this, 'handle_oauth_callback'));
        add_action('wp_ajax_nopriv_mwf_sso_callback', array($this, 'handle_oauth_callback'));
        
        // Add logout hook for Single Logout (SLO)
        add_action('wp_logout', array($this, 'handle_logout'));
    }

    public function maybe_redirect_to_sso() {
        // Check if SSO is enabled
        if (!get_option('mwf_sso_enabled', false)) {
            error_log('SSO: SSO not enabled');
            return;
        }

        error_log('SSO: maybe_redirect_to_sso called, is_login_page: ' . ($this->is_login_page() ? 'true' : 'false') . ', is_user_logged_in: ' . (is_user_logged_in() ? 'true' : 'false'));
        error_log('SSO: Current user ID: ' . get_current_user_id());
        error_log('SSO: GET params: ' . print_r($_GET, true));
        error_log('SSO: pagenow: ' . $GLOBALS['pagenow']);

        // Only redirect on wp-login.php when not logged in
        if ($this->is_login_page() && !is_user_logged_in()) {
            // Don't redirect if currently processing logout action
            if (isset($_GET['action']) && $_GET['action'] === 'logout') {
                error_log('SSO: Blocking redirect because action=logout');
                return;
            }

            error_log('SSO: Calling redirect_to_oauth');
            // DO redirect if user has been logged out - this is when SSO should take over
            $this->redirect_to_oauth();
        } else {
            error_log('SSO: Not redirecting - not on login page or user is logged in');
        }
    }

    public function add_admin_menu() {
        add_options_page(
            'MWF SSO Settings',
            'MWF SSO',
            'manage_options',
            'mwf-sso-settings',
            array($this, 'settings_page')
        );
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('mwf_sso_enabled', isset($_POST['mwf_sso_enabled']));
            update_option('mwf_sso_client_id', sanitize_text_field($_POST['mwf_sso_client_id']));
            update_option('mwf_sso_client_secret', sanitize_text_field($_POST['mwf_sso_client_secret']));
            update_option('mwf_sso_admin_url', sanitize_text_field($_POST['mwf_sso_admin_url']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }

        $enabled = get_option('mwf_sso_enabled', false);
        $client_id = get_option('mwf_sso_client_id', '');
        $client_secret = get_option('mwf_sso_client_secret', '');
        $admin_url = get_option('mwf_sso_admin_url', 'https://admin.soilsync.shop');

        ?>
        <div class="wrap">
            <h1>MWF SSO Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th scope="row">Enable SSO</th>
                        <td>
                            <input type="checkbox" name="mwf_sso_enabled" value="1" <?php checked($enabled); ?> />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Client ID</th>
                        <td>
                            <input type="text" name="mwf_sso_client_id" value="<?php echo esc_attr($client_id); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Client Secret</th>
                        <td>
                            <input type="password" name="mwf_sso_client_secret" value="<?php echo esc_attr($client_secret); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Admin URL</th>
                        <td>
                            <input type="url" name="mwf_sso_admin_url" value="<?php echo esc_attr($admin_url); ?>" class="regular-text" />
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function custom_login_url($login_url, $redirect, $force_reauth) {
        // NEVER override any URLs related to logout
        if (strpos($login_url, 'action=logout') !== false) {
            return $login_url;
        }
        if (strpos($login_url, 'loggedout') !== false) {
            return $login_url;
        }
        if ($redirect && strpos($redirect, 'action=logout') !== false) {
            return $login_url;
        }
        if ($redirect && strpos($redirect, 'loggedout') !== false) {
            return $login_url;
        }
        if (isset($_GET['action']) && $_GET['action'] === 'logout') {
            return $login_url;
        }
        if (isset($_GET['loggedout'])) {
            return $login_url;
        }
        
        if (get_option('mwf_sso_enabled', false)) {
            $login_url = $this->admin_url . '/sso/login?redirect=' . urlencode($redirect);
        }
        return $login_url;
    }

    public function authenticate_via_oauth($user, $username, $password) {
        // If SSO is enabled, prevent default authentication
        if (get_option('mwf_sso_enabled', false)) {
            return null; // Let OAuth handle it
        }
        return $user;
    }

    public function handle_oauth_callback() {
        // Basic debugging - log that we got here
        error_log('SSO: Callback started');

        if (!isset($_GET['token'])) {
            error_log('SSO: No token provided');
            wp_die('No SSO token provided');
        }

        $token = sanitize_text_field($_GET['token']);
        error_log('SSO: Token received: ' . substr($token, 0, 50) . '...');

        try {
            // Simple JWT validation (header.payload.signature)
            $parts = explode('.', $token);
            error_log('SSO: Token parts: ' . count($parts));

            if (count($parts) !== 3) {
                error_log('SSO: Invalid token format - wrong parts count');
                wp_die('Invalid token format: wrong number of parts');
            }

            // Decode payload (URL-safe base64)
            $payload_b64 = str_replace(['-', '_'], ['+', '/'], $parts[1]);
            // Add padding if needed
            $payload_b64 = str_pad($payload_b64, strlen($payload_b64) % 4, '=', STR_PAD_RIGHT);
            error_log('SSO: Payload b64: ' . substr($payload_b64, 0, 50) . '...');

            $payload_json = base64_decode($payload_b64);

            if ($payload_json === false) {
                error_log('SSO: Base64 decode failed');
                wp_die('Invalid base64 in token payload');
            }

            error_log('SSO: Payload JSON: ' . substr($payload_json, 0, 100) . '...');

            $payload = json_decode($payload_json, true);

            if (!$payload) {
                error_log('SSO: JSON decode failed');
                wp_die('Invalid JSON in token payload');
            }

            error_log('SSO: Payload decoded successfully');

            // Verify token is for WordPress
            if (!isset($payload['aud']) || $payload['aud'] !== 'wordpress') {
                error_log('SSO: Wrong audience: ' . ($payload['aud'] ?? 'none'));
                wp_die('Invalid token audience: ' . ($payload['aud'] ?? 'none'));
            }

            // Check if token is expired
            if (isset($payload['exp']) && time() > $payload['exp']) {
                error_log('SSO: Token expired');
                wp_die('Token expired');
            }

            if (!isset($payload['user'])) {
                error_log('SSO: No user data in token');
                wp_die('No user data in token');
            }

            $user_data = $payload['user'];
            error_log('SSO: User data found: ' . $user_data['email']);

        } catch (Exception $e) {
            error_log('SSO: Exception: ' . $e->getMessage());
            wp_die('JWT processing error: ' . $e->getMessage());
        }

        error_log('SSO: JWT validation passed, proceeding with user creation');

        // Find or create WP user
        $wp_user = get_user_by('email', $user_data['email']);
        if (!$wp_user) {
            error_log('SSO: Creating new user: ' . $user_data['email']);
            $wp_user_id = wp_create_user($user_data['email'], wp_generate_password(), $user_data['email']);
            if (is_wp_error($wp_user_id)) {
                error_log('SSO: User creation failed: ' . $wp_user_id->get_error_message());
                wp_die('Failed to create WordPress user: ' . $wp_user_id->get_error_message());
            }
            $wp_user = get_user_by('id', $wp_user_id);
            wp_update_user([
                'ID' => $wp_user_id,
                'display_name' => $user_data['name'],
                'first_name' => $user_data['name']
            ]);
        } else {
            error_log('SSO: User already exists: ' . $user_data['email']);
        }

        // Log in the user (session-only cookie, not persistent)
        wp_set_current_user($wp_user->ID);
        wp_set_auth_cookie($wp_user->ID, false); // false = session only, clears on browser close
        do_action('wp_login', $wp_user->user_login, $wp_user);

        error_log('SSO: User logged in successfully, redirecting');

        // Redirect to admin or intended page
        $redirect_to = !empty($_GET['redirect_to']) ? $_GET['redirect_to'] : admin_url();
        wp_safe_redirect($redirect_to);
        exit;
    }

    private function getJwtSecret() {
        // Use the same secret as Laravel (app.key)
        return 'base64:/EUGyMqw/h+Nrm9tWpKC4eZGHMpLjW3iv1XoRu5t4sk=';
    }

    public function is_login_page() {
        return in_array($GLOBALS['pagenow'], ['wp-login.php']);
    }

    public function redirect_to_oauth() {
        error_log('SSO: redirect_to_oauth called');

        // Prevent redirect loops - don't redirect if we're already going to SSO
        if (isset($_GET['redirect']) && strpos($_GET['redirect'], '/sso/login') !== false) {
            error_log('SSO: Preventing redirect loop');
            return;
        }

        $redirect_to = isset($_GET['redirect_to']) ? $_GET['redirect_to'] : admin_url();

        // Check if this redirect is happening after a recent logout
        $is_after_logout = isset($_GET['loggedout']) ||
                          (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'loggedout=true') !== false);

        error_log('SSO: is_after_logout: ' . ($is_after_logout ? 'true' : 'false'));

        $params = [
            'redirect' => $redirect_to
        ];

        if ($is_after_logout) {
            $params['after_logout'] = '1';
            error_log('SSO: Adding after_logout=1 to params');
        }

        $sso_url = $this->admin_url . '/sso/login?' . http_build_query($params);
        error_log('SSO: Redirecting to: ' . $sso_url);

        wp_redirect($sso_url);
        exit;
    }    public function handle_logout() {
        error_log('SSO: handle_logout called - starting dual logout');

        // When WordPress user logs out, also log out from Laravel SSO
        // This ensures both systems are logged out simultaneously
        $logout_url = $this->admin_url . '/sso/logout';

        error_log('SSO: Making logout request to: ' . $logout_url);

        // Make a blocking request to Laravel logout to ensure it completes
        $response = wp_remote_get($logout_url, [
            'timeout' => 10,
            'blocking' => true, // Wait for response to ensure logout completes
            'headers' => [
                'User-Agent' => 'WordPress SSO Logout'
            ]
        ]);

        if (is_wp_error($response)) {
            error_log('SSO: Logout request failed: ' . $response->get_error_message());
        } else {
            error_log('SSO: Logout request completed with status: ' . wp_remote_retrieve_response_code($response));
        }

        // After logout, redirect to WordPress homepage to prevent redirect loops
        // This ensures users don't get stuck in FarmOS -> SSO -> FarmOS loops
        wp_redirect(home_url());
        exit;
    }
}

new MWF_SSO();