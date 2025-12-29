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
}
add_action('wp_enqueue_scripts', 'middleworld_farms_enqueue_styles');

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
