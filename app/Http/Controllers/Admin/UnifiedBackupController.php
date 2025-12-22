<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class UnifiedBackupController extends Controller
{
    public function index()
    {
        // Get backup status from our custom backup system
        $backupStatus = $this->getCustomBackupStatus();

        // Get configured sites from unified_backup config
        $sites = config('unified_backup.sites', []);

        // Calculate last backup for each site
        $sites = $this->addLastBackupInfo($sites);

        return view('admin.unified-backup.index', compact('backupStatus', 'sites'));
    }

    public function run(Request $request)
    {
        try {
            $site = $request->input('site', 'admin');
            $cloud = $request->input('cloud', false);

            // Get site configuration
            $sites = config('unified_backup.sites', []);
            $siteConfig = $sites[$site] ?? null;

            if (!$siteConfig) {
                return response()->json([
                    'success' => false,
                    'message' => 'Site configuration not found'
                ], 404);
            }

            // Build command based on site type
            switch ($siteConfig['type']) {
                case 'custom':
                    $command = ($siteConfig['command'] ?? 'backup:custom --site=' . $site) . ' --force';
                    break;
                case 'spatie':
                    $command = 'backup:run --only-files'; // Spatie Laravel backup
                    break;
                case 'remote_api':
                    // For remote API backups, we'd need to make HTTP calls
                    // For now, return not implemented
                    return response()->json([
                        'success' => false,
                        'message' => 'Remote API backup not yet implemented'
                    ], 501);
                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Unknown backup type: ' . $siteConfig['type']
                    ], 400);
            }

            // Note: --cloud flag removed as it causes instant failures
            // Cloud backup can be configured in config/backup.php destinations
            
            // Run backup synchronously (not queued) to avoid queue worker dependency
            // Use output buffering to capture progress
            Artisan::call($command);
            $output = Artisan::output();

            return response()->json([
                'success' => true,
                'message' => ucfirst($siteConfig['label'] ?? $site) . ' backup completed successfully!',
                'output' => $output,
                'background' => false
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Backup failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function status()
    {
        // Get current backup status from our custom backup system
        return response()->json($this->getCustomBackupStatus());
    }

    private function getCustomBackupStatus(): array
    {
        $backupDir = storage_path('backups');
        
        // Get both .zip and .tar.gz files
        $zipBackups = glob("$backupDir/*-backup_*.zip");
        $tarBackups = glob("$backupDir/*-backup_*.tar.gz");
        $backups = array_merge($zipBackups, $tarBackups);

        $totalBackups = count($backups);
        $totalSize = 0;
        $latestBackup = null;
        $latestSize = 0;

        if (!empty($backups)) {
            // Sort by modification time (newest first)
            usort($backups, function($a, $b) {
                return filemtime($b) - filemtime($a);
            });

            $latestBackupFile = $backups[0];
            $latestSize = filesize($latestBackupFile);
            $totalSize = array_sum(array_map('filesize', $backups));

            // Format as relative time (e.g., "2 hours ago")
            $latestBackup = $this->formatTimeAgo(filemtime($latestBackupFile));
        }

        // Consider healthy if we have at least 1 backup from the last 7 days
        $isHealthy = $totalBackups > 0 && (!empty($backups) && (time() - filemtime($backups[0]) < 7 * 24 * 60 * 60));

        return [
            'total_backups' => $totalBackups,
            'latest_backup' => $latestBackup,
            'latest_size' => $latestSize,
            'total_size' => $totalSize,
            'is_healthy' => $isHealthy,
        ];
    }

    private function formatTimeAgo(int $timestamp): string
    {
        $diff = time() - $timestamp;

        if ($diff < 60) {
            return 'Just now';
        } elseif ($diff < 3600) {
            $mins = floor($diff / 60);
            return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
        } else {
            $days = floor($diff / 86400);
            return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
        }
    }

    public function files()
    {
        $backupDir = storage_path('backups');
        $backups = [];

        if (is_dir($backupDir)) {
            // Get both .zip and .tar.gz files
            $zipFiles = glob("$backupDir/*.zip");
            $tarFiles = glob("$backupDir/*.tar.gz");
            $files = array_merge($zipFiles, $tarFiles);
            
            usort($files, function($a, $b) {
                return filemtime($b) - filemtime($a);
            });

            foreach ($files as $file) {
                $filename = basename($file);

                // Parse filename to extract site and timestamp
                // Format: {site}-backup_{timestamp}.zip or {site}-backup_{timestamp}.tar.gz
                if (preg_match('/^(.+?)-backup_(.+)\.(zip|tar\.gz)$/', $filename, $matches)) {
                    $site = $matches[1];
                    $timestamp = $matches[2];
                } else {
                    // Fallback for old format
                    $site = 'unknown';
                    $timestamp = str_replace(['custom-backup_', '.zip', '.tar.gz'], '', $filename);
                }

                $backups[] = [
                    'filename' => $filename,
                    'site' => $site,
                    'size' => filesize($file),
                    'created' => filemtime($file),
                    'path' => $file,
                    'timestamp' => $timestamp,
                ];
            }
        }

        return response()->json($backups);
    }

    private function addLastBackupInfo(array $sites): array
    {
        $backupDir = storage_path('backups');

        if (!is_dir($backupDir)) {
            // No backup directory, return sites as-is
            foreach ($sites as $key => $site) {
                $sites[$key]['last_backup'] = 'Never';
            }
            return $sites;
        }

        // Get all backup files (both .zip and .tar.gz)
        $zipFiles = glob("$backupDir/*.zip");
        $tarFiles = glob("$backupDir/*.tar.gz");
        $files = array_merge($zipFiles, $tarFiles);
        $backupsBySite = [];

        // Parse files and group by site
        foreach ($files as $file) {
            $filename = basename($file);

            // Parse filename to extract site
            if (preg_match('/^(.+?)-backup_(.+)\.zip$/', $filename, $matches)) {
                $site = $matches[1];
            } else {
                // Fallback for old format
                $site = 'unknown';
            }

            if (!isset($backupsBySite[$site])) {
                $backupsBySite[$site] = [];
            }

            $backupsBySite[$site][] = [
                'file' => $file,
                'mtime' => filemtime($file),
                'filename' => $filename
            ];
        }

        // Create mapping from config keys to backup site identifiers
        $siteMapping = [
            'admin.middleworldfarms.org' => 'admin',
            'farmos.middleworldfarms.org' => 'farmos',
            'middleworldfarms.org' => 'middleworldfarms',
            'middleworld.farm' => 'middleworld',
        ];

        // Find the most recent backup for each site
        foreach ($sites as $key => $site) {
            $backupSiteKey = $siteMapping[$key] ?? $key;

            if (isset($backupsBySite[$backupSiteKey])) {
                // Sort backups by modification time (newest first)
                usort($backupsBySite[$backupSiteKey], function($a, $b) {
                    return $b['mtime'] - $a['mtime'];
                });

                $latestBackup = $backupsBySite[$backupSiteKey][0];
                $sites[$key]['last_backup'] = $this->formatTimeAgo($latestBackup['mtime']);
            } else {
                $sites[$key]['last_backup'] = 'Never';
            }
        }

        return $sites;
    }

    public function download($filename)
    {
        $backupDir = storage_path('backups');
        $filePath = $backupDir . '/' . $filename;

        if (!file_exists($filePath)) {
            abort(404, 'Backup file not found');
        }

        return response()->download($filePath);
    }

    public function delete(Request $request)
    {
        $filename = $request->input('filename');
        
        if (!$filename) {
            return response()->json([
                'success' => false,
                'message' => 'Filename is required'
            ], 400);
        }

        $backupDir = storage_path('backups');
        $filePath = $backupDir . '/' . $filename;

        // Security check: ensure the file is actually in the backups directory
        if (!file_exists($filePath) || dirname(realpath($filePath)) !== realpath($backupDir)) {
            return response()->json([
                'success' => false,
                'message' => 'Backup file not found'
            ], 404);
        }

        try {
            unlink($filePath);
            
            return response()->json([
                'success' => true,
                'message' => 'Backup file deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete backup file: ' . $e->getMessage()
            ], 500);
        }
    }
}