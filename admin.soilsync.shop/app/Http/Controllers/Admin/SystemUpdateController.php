<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SystemUpdateController extends Controller
{
    /**
     * Update server URL (centralized)
     */
    private $updateServerUrl;
    
    /**
     * API key for authentication
     */
    private $apiKey;
    
    /**
     * Local version file
     */
    private $versionFile;
    
    public function __construct()
    {
        $this->versionFile = base_path('version.json');
        $this->updateServerUrl = env('UPDATE_SERVER_URL', 'https://updates.soilsync.shop');
        $this->apiKey = env('UPDATE_SERVER_API_KEY', '');
    }
    
    /**
     * Show system updates page
     */
    public function index()
    {
        $currentVersion = $this->getCurrentVersion();
        
        return view('admin.system.updates', [
            'current_version' => $currentVersion,
            'update_server' => $this->updateServerUrl
        ]);
    }
    
    /**
     * Check for available updates
     */
    public function checkForUpdates(Request $request)
    {
        try {
            $currentVersion = $this->getCurrentVersion();
            
            // Build update server URL
            $updateUrl = rtrim($this->updateServerUrl, '/') . '/api/updates/' . 
                         ($currentVersion['system_version'] ?? '0.0.0');
            
            // Call update server with API key
            $response = Http::timeout(10)
                ->withoutVerifying() // Skip SSL verification for update server
                ->withHeaders([
                    'X-API-Key' => $this->apiKey,
                    'User-Agent' => 'MWF-Admin/' . ($currentVersion['system_version'] ?? '0.0.0')
                ])
                ->get($updateUrl);
            
            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to connect to update server: ' . $response->status()
                ], 500);
            }
            
            $updates = $response->json();
            
            // Compare versions
            $hasUpdates = $updates['updates_available'] ?? false;
            
            return response()->json([
                'success' => true,
                'has_updates' => $hasUpdates,
                'current_version' => $currentVersion['system_version'] ?? 'unknown',
                'latest_version' => $updates['latest_version'] ?? 'unknown',
                'updates' => $updates['updates'] ?? [],
                'checked_at' => now()->toIso8601String()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Update check failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Update check failed: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get plugin versions from WordPress
     */
    public function checkPluginVersions(Request $request)
    {
        try {
            $plugins = [];
            
            // Determine WordPress plugin directory based on environment
            $pluginDir = $this->getWordPressPluginPath();
            
            $mwfPlugins = [
                'mwf-integration',
                'mwf-solidarity-pricing',
                'mwf-subscriptions',
                'mwf-team-members',
                'mwf-reviews'
            ];
            
            foreach ($mwfPlugins as $pluginSlug) {
                $pluginFile = "$pluginDir/$pluginSlug/$pluginSlug.php";
                
                if (file_exists($pluginFile)) {
                    $version = $this->parsePluginVersion($pluginFile);
                    $plugins[$pluginSlug] = [
                        'installed' => true,
                        'version' => $version,
                        'path' => $pluginFile
                    ];
                } else {
                    $plugins[$pluginSlug] = [
                        'installed' => false,
                        'version' => null,
                        'path' => null
                    ];
                }
            }
            
            return response()->json([
                'success' => true,
                'plugins' => $plugins
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check plugin versions: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Parse version from plugin file
     */
    private function parsePluginVersion($filePath)
    {
        $content = file_get_contents($filePath);
        
        // Look for "Version: X.X.X" in plugin header
        if (preg_match('/Version:\s*([0-9.]+)/i', $content, $matches)) {
            return $matches[1];
        }
        
        return 'unknown';
    }
    
    /**
     * Get current system version
     */
    private function getCurrentVersion()
    {
        try {
            // Try multiple possible locations for version.json
            $possiblePaths = [
                base_path('version.json'),
                __DIR__ . '/../../../../version.json',
                '/opt/sites/' . env('APP_NAME', 'admin.middleworldfarms.org') . '/version.json',
            ];
            
            foreach ($possiblePaths as $path) {
                if (file_exists($path) && is_readable($path)) {
                    $content = file_get_contents($path);
                    $version = json_decode($content, true);
                    
                    if ($version && isset($version['system_version'])) {
                        return $version;
                    }
                }
            }
            
            // Fallback: return default values
            return [
                'system_version' => '1.0.0', // Hardcoded for now
                'release_date' => '2026-01-02',
                'release_name' => 'Gold Master Initial Release',
                'components' => []
            ];
            
        } catch (\Exception $e) {
            Log::error('Failed to read version.json', [
                'error' => $e->getMessage(),
                'base_path' => base_path(),
                'version_file' => $this->versionFile
            ]);
            
            return [
                'system_version' => '1.0.0',
                'release_date' => 'unknown',
                'components' => []
            ];
        }
    }
    
    /**
     * Download and install update
     */
    public function installUpdate(Request $request)
    {
        $request->validate([
            'version' => 'required|string'
        ]);
        
        try {
            // Get update package URL
            $packageUrl = $this->updateServerUrl . '/download/' . $request->version;
            
            // Download update package
            $response = Http::timeout(60)
                ->withoutVerifying() // Skip SSL verification for update server
                ->get($packageUrl);
            
            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to download update package'
                ], 500);
            }
            
            // Save to temporary location
            $updateDir = storage_path('app/updates');
            if (!is_dir($updateDir)) {
                mkdir($updateDir, 0755, true);
            }
            
            $packageFile = $updateDir . '/update-' . $request->version . '.zip';
            file_put_contents($packageFile, $response->body());
            
            return response()->json([
                'success' => true,
                'message' => 'Update package downloaded. Please apply manually.',
                'package' => $packageFile,
                'instructions' => [
                    '1. Backup your site',
                    '2. Extract the update package',
                    '3. Review changes in .new files',
                    '4. Apply changes carefully',
                    '5. Test thoroughly',
                    '6. Run: php artisan config:clear'
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Update installation failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Update installation failed: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get WordPress plugin directory path
     */
    private function getWordPressPluginPath()
    {
        // Check environment-specific configuration
        $configuredPath = config('services.wordpress.plugin_path');
        if ($configuredPath && file_exists($configuredPath)) {
            return $configuredPath;
        }
        
        // Try common paths based on environment
        $possiblePaths = [
            // Production: Laravel at /opt/sites/, WordPress at /var/www/vhosts/
            '/var/www/vhosts/' . env('WORDPRESS_DOMAIN', 'middleworldfarms.org') . '/httpdocs/wp-content/plugins',
            // Demo: Laravel and WordPress in same parent directory
            base_path('../httpdocs/wp-content/plugins'),
            // Fallback: check if we're in demo environment
            base_path('../../../../var/www/vhosts/soilsync.shop/httpdocs/wp-content/plugins'),
        ];
        
        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }
        
        // Last resort: use environment variable or default
        return env('WORDPRESS_PLUGIN_PATH', base_path('../httpdocs/wp-content/plugins'));
    }
}
