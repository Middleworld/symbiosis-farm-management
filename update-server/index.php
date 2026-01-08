<?php

/**
 * Update Server API
 * 
 * This standalone API serves update information to all customer installations.
 * Hosted on: updates.soilsync.shop
 * 
 * Endpoints:
 *   GET  /api/version                  - Get current gold master version
 *   GET  /api/updates/{version}        - List available updates for version
 *   GET  /api/download/{package}       - Download update package (requires API key)
 */

// Load configuration
$config = require_once __DIR__ . '/config.php';
$api_keys = $config['api_keys'] ?? [];
$rate_limit_config = $config['rate_limit'] ?? ['enabled' => true, 'max_requests' => 100, 'window' => 3600];

// Allow CORS for customer sites
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');
header('Content-Type: application/json');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Configuration
define('UPDATES_DIR', __DIR__ . '/packages');
define('VERSION_FILE', __DIR__ . '/version.json');

// Simple router
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Remove trailing slash
$uri = rtrim($uri, '/');

// Routes
switch (true) {
    case $uri === '/api/version' && $method === 'GET':
        getVersion();
        break;
        
    case preg_match('/^\/api\/updates\/(.+)$/', $uri, $matches) && $method === 'GET':
        getUpdates($matches[1]);
        break;
        
    case preg_match('/^\/api\/download\/(.+)$/', $uri, $matches) && $method === 'GET':
        downloadUpdate($matches[1]);
        break;
        
    case $uri === '/api/plugin-info' && $method === 'GET':
        getPluginInfo();
        break;
        
    case $uri === '/ping' && $method === 'GET':
        echo json_encode(['status' => 'ok', 'timestamp' => time()]);
        break;
        
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found']);
        break;
}

/**
 * Get current system version
 */
function getVersion() {
    if (!file_exists(VERSION_FILE)) {
        http_response_code(500);
        echo json_encode(['error' => 'Version file not found']);
        return;
    }
    
    $version = json_decode(file_get_contents(VERSION_FILE), true);
    echo json_encode($version);
}

/**
 * Get available updates for a specific version
 */
function getUpdates($currentVersion) {
    // Authenticate request
    $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? $_GET['key'] ?? '';
    
    if (!validateApiKey($apiKey)) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid or missing API key']);
        return;
    }
    
    // Sanitize version
    $currentVersion = preg_replace('/[^0-9.]/', '', $currentVersion);
    
    // Load manifest
    $manifestFile = UPDATES_DIR . '/manifest.json';
    if (!file_exists($manifestFile)) {
        echo json_encode([
            'updates_available' => false,
            'latest_version' => $currentVersion,
            'updates' => []
        ]);
        return;
    }
    
    $manifest = json_decode(file_get_contents($manifestFile), true);
    
    // Determine latest version
    $latestVersion = $manifest['version'] ?? $currentVersion;
    $hasUpdates = version_compare($latestVersion, $currentVersion, '>');
    
    // Get available updates
    $availableUpdates = [];
    if ($hasUpdates && isset($manifest['updates'])) {
        foreach ($manifest['updates'] as $version => $updateInfo) {
            if (version_compare($version, $currentVersion, '>')) {
                $packageFile = UPDATES_DIR . "/$version.tar.gz";
                $availableUpdates[] = [
                    'version' => $version,
                    'release_date' => $updateInfo['release_date'] ?? date('Y-m-d'),
                    'changelog' => $updateInfo['changelog'] ?? '',
                    'package_url' => '/api/download/' . $version,
                    'package_size' => file_exists($packageFile) ? formatBytes(filesize($packageFile)) : 'Unknown',
                    'checksum' => $updateInfo['checksum'] ?? ''
                ];
            }
        }
    }
    
    echo json_encode([
        'updates_available' => $hasUpdates,
        'latest_version' => $latestVersion,
        'updates' => $availableUpdates
    ]);
}


/**
 * Download update package
 */
function downloadUpdate($updateId) {
    // Authenticate request
    $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? $_GET['key'] ?? '';
    
    if (!validateApiKey($apiKey)) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid or missing API key']);
        return;
    }
    
    // Sanitize update ID
    $updateId = preg_replace('/[^a-zA-Z0-9._-]/', '', $updateId);
    
    $packageFile = UPDATES_DIR . "/$updateId.tar.gz";
    
    if (!file_exists($packageFile)) {
        http_response_code(404);
        echo json_encode(['error' => 'Update package not found']);
        return;
    }
    
    // Log download
    logAccess($apiKey, "download/$updateId");
    
    // Send file
    header('Content-Type: application/gzip');
    header('Content-Disposition: attachment; filename="' . basename($packageFile) . '"');
    header('Content-Length: ' . filesize($packageFile));
    readfile($packageFile);
    exit;
}

/**
 * Validate API key
 */
function validateApiKey($apiKey) {
    global $api_keys;
    
    if (empty($apiKey)) {
        return false;
    }
    
    // Check if key exists in config (simple key => domain structure)
    if (isset($api_keys[$apiKey])) {
        $customer = $api_keys[$apiKey];
        
        // Check rate limiting
        if (!checkRateLimit($customer)) {
            return false;
        }
        return true;
    }
    
    return false;
}

/**
 * Check rate limiting
 */
function checkRateLimit($customer) {
    // Simple rate limiting (10 requests per minute)
    $rateFile = __DIR__ . '/logs/rate-' . md5($customer) . '.txt';
    
    if (!file_exists(dirname($rateFile))) {
        mkdir(dirname($rateFile), 0755, true);
    }
    
    $now = time();
    $requests = [];
    
    if (file_exists($rateFile)) {
        $requests = array_filter(
            explode("\n", file_get_contents($rateFile)),
            function($timestamp) use ($now) {
                return $timestamp > ($now - 60);
            }
        );
    }
    
    if (count($requests) >= 10) {
        http_response_code(429);
        echo json_encode(['error' => 'Rate limit exceeded']);
        return false;
    }
    
    $requests[] = $now;
    file_put_contents($rateFile, implode("\n", $requests));
    
    return true;
}

/**
 * Log API access
 */
function logAccess($apiKey, $endpoint) {
    $logFile = __DIR__ . '/logs/access.log';
    
    if (!file_exists(dirname($logFile))) {
        mkdir(dirname($logFile), 0755, true);
    }
    
    $logEntry = sprintf(
        "[%s] %s - %s - %s\n",
        date('Y-m-d H:i:s'),
        $_SERVER['REMOTE_ADDR'],
        substr($apiKey, 0, 8) . '...',
        $endpoint
    );
    
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

/**
 * Format bytes to human readable
 */
function formatBytes($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    
    return round($bytes, 2) . ' ' . $units[$i];
}

/**
 * Get plugin update information (WordPress.org style API)
 */
function getPluginInfo() {
    $plugin_slug = $_GET['plugin'] ?? '';
    $current_version = $_GET['version'] ?? '0.0.0';
    
    if (empty($plugin_slug)) {
        http_response_code(400);
        echo json_encode(['error' => 'Plugin slug required']);
        return;
    }
    
    // Load plugin manifest
    $pluginManifestFile = UPDATES_DIR . '/plugins.json';
    if (!file_exists($pluginManifestFile)) {
        echo json_encode([
            'update_available' => false,
            'current_version' => $current_version
        ]);
        return;
    }
    
    $plugins = json_decode(file_get_contents($pluginManifestFile), true);
    
    if (!isset($plugins[$plugin_slug])) {
        echo json_encode([
            'update_available' => false,
            'current_version' => $current_version
        ]);
        return;
    }
    
    $plugin = $plugins[$plugin_slug];
    $latest_version = $plugin['version'];
    $update_available = version_compare($latest_version, $current_version, '>');
    
    // WordPress.org style response
    $response = [
        'slug' => $plugin_slug,
        'plugin' => $plugin_slug . '/' . $plugin_slug . '.php',
        'new_version' => $latest_version,
        'url' => 'https://updates.soilsync.shop',
        'package' => 'https://updates.soilsync.shop/api/download/plugin-' . $plugin_slug . '-' . $latest_version,
        'tested' => $plugin['tested'] ?? '6.4',
        'requires_php' => $plugin['requires_php'] ?? '8.2',
        'upgrade_notice' => $plugin['upgrade_notice'] ?? '',
        'sections' => [
            'description' => $plugin['description'] ?? '',
            'changelog' => $plugin['changelog'] ?? ''
        ]
    ];
    
    echo json_encode($response);
}
