<?php
/**
 * Update Server Configuration
 * 
 * Manages API keys, customer domains, and security settings
 */

return array (
  'api_keys' => 
  array (
    '81cb3325e637b0bed743260094c4a759d8d4e776d76264ab2351d5b8c444a650' => 'admin.soilsync.shop',
    'e2f88eaa1ac5aaf4d702e8d2a5bf13f24f9d4d42e0067335ab433fd29198333f' => 'middleworldfarms.org',
  ),
  'rate_limit' => 
  array (
    'enabled' => true,
    'max_requests' => 100,
    'window' => 3600,
  ),
  'packages' => 
  array (
    'directory' => '/var/www/vhosts/soilsync.shop/update-server/packages',
    'max_versions' => 10,
    'auto_cleanup' => true,
  ),
  'security' => 
  array (
    'require_https' => true,
    'verify_checksum' => true,
    'allowed_ips' => 
    array (
    ),
  ),
  'logging' => 
  array (
    'enabled' => true,
    'log_file' => '/var/www/vhosts/soilsync.shop/update-server/logs/access.log',
    'log_downloads' => true,
    'log_failed_auth' => true,
  ),
);
