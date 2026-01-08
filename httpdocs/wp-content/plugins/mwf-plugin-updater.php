<?php
/**
 * MWF Plugin Updater
 * 
 * Connects WordPress plugins to updates.soilsync.shop for automatic updates
 * Works like WordPress.org but for custom plugins
 * 
 * Usage in your plugin:
 * require_once(WP_CONTENT_DIR . '/plugins/mwf-plugin-updater.php');
 * new MWF_Plugin_Updater(__FILE__, 'your-plugin-slug', '1.0.0');
 */

if (!defined('ABSPATH')) exit;

class MWF_Plugin_Updater {
    private $plugin_file;
    private $plugin_slug;
    private $version;
    private $update_url = 'https://updates.soilsync.shop/api/plugin-info';
    private $cache_key;
    private $cache_allowed = true;
    
    public function __construct($plugin_file, $plugin_slug, $version) {
        $this->plugin_file = $plugin_file;
        $this->plugin_slug = $plugin_slug;
        $this->version = $version;
        $this->cache_key = 'mwf_plugin_update_' . $plugin_slug;
        
        // Hook into WordPress update system
        add_filter('pre_set_site_transient_update_plugins', [$this, 'check_update']);
        add_filter('plugins_api', [$this, 'plugin_info'], 20, 3);
    }
    
    /**
     * Check for plugin updates
     */
    public function check_update($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }
        
        // Get update info from our server
        $update_info = $this->get_update_info();
        
        if ($update_info && version_compare($this->version, $update_info->new_version, '<')) {
            $plugin_basename = plugin_basename($this->plugin_file);
            $transient->response[$plugin_basename] = $update_info;
        }
        
        return $transient;
    }
    
    /**
     * Get plugin information for "View Details" link
     */
    public function plugin_info($result, $action, $args) {
        if ($action !== 'plugin_information') {
            return $result;
        }
        
        if (empty($args->slug) || $args->slug !== $this->plugin_slug) {
            return $result;
        }
        
        $update_info = $this->get_update_info();
        
        if (!$update_info) {
            return $result;
        }
        
        return $update_info;
    }
    
    /**
     * Fetch update information from update server
     */
    private function get_update_info() {
        // Check cache first
        if ($this->cache_allowed) {
            $cached = get_transient($this->cache_key);
            if (false !== $cached) {
                return $cached;
            }
        }
        
        // Build request URL
        $url = add_query_arg([
            'plugin' => $this->plugin_slug,
            'version' => $this->version
        ], $this->update_url);
        
        // Fetch from update server
        $response = wp_remote_get($url, [
            'timeout' => 10,
            'headers' => [
                'Accept' => 'application/json'
            ]
        ]);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body);
        
        if (!$data || empty($data->new_version)) {
            return false;
        }
        
        // Convert to WordPress update object format
        $update = (object) [
            'slug' => $this->plugin_slug,
            'plugin' => plugin_basename($this->plugin_file),
            'new_version' => $data->new_version,
            'url' => $data->url ?? '',
            'package' => $data->package ?? '',
            'tested' => $data->tested ?? '',
            'requires_php' => $data->requires_php ?? '',
            'sections' => (array) ($data->sections ?? []),
            'upgrade_notice' => $data->upgrade_notice ?? ''
        ];
        
        // Cache for 12 hours
        set_transient($this->cache_key, $update, 12 * HOUR_IN_SECONDS);
        
        return $update;
    }
    
    /**
     * Clear update cache (call this after updating)
     */
    public function clear_cache() {
        delete_transient($this->cache_key);
    }
}
