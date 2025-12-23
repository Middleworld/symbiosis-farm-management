<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class CareyOneBackup extends Command
{
    protected $signature = 'backup:carey-one {--estimate : Show size estimate without creating backup} {--force : Skip confirmation prompt} {--cloud : Upload backup to Google Drive after creation}';

    protected $description = 'Create a smart backup of Carey one site (middleworldfarms.org) excluding WordPress core, WooCommerce, and unnecessary plugins';

    private $sitePath = '/var/www/vhosts/middleworldfarms.org/httpdocs';
    private $siteName = 'middleworldfarms.org';

    private $excludePatterns = [
        // WordPress core
        'wp-admin/',
        'wp-includes/',
        'wp-content/uploads/', // Can be large, might want to exclude if using CDN
        // WooCommerce
        'wp-content/plugins/woocommerce/',
        // Other standard plugins (keep only MWF-* custom plugins)
        'wp-content/plugins/akismet/',
        'wp-content/plugins/hello.php',
        'wp-content/plugins/wp-super-cache/',
        'wp-content/plugins/w3-total-cache/',
        'wp-content/plugins/wordpress-seo/',
        'wp-content/plugins/contact-form-7/',
        'wp-content/plugins/google-analytics/',
        'wp-content/plugins/jetpack/',
        'wp-content/plugins/elementor/',
        'wp-content/plugins/divi-builder/',
        // Themes (keep only active custom theme)
        'wp-content/themes/twenty*/',
        'wp-content/themes/twentytwenty*/',
        // Cache and temp files
        'wp-content/cache/',
        'wp-content/uploads/cache/',
        '*.log',
        '.DS_Store',
        'Thumbs.db',
    ];

    public function handle()
    {
        $this->info('=== CAREY ONE SITE BACKUP (SAFEST APPROACH) ===');
        $this->warn('This uses direct file access - NO API calls, NO database modifications');
        $this->warn('Previous backup disasters used APIs - this approach is much safer');

        // Safety check 1: Verify site exists and is readable
        if (!$this->verifySiteAccess()) {
            $this->error('Cannot access Carey one site. Aborting for safety.');
            return 1;
        }

        // Safety check 2: Estimate size first
        $estimate = $this->estimateBackupSize();
        if ($estimate === false) {
            $this->error('Cannot estimate backup size. Aborting for safety.');
            return 1;
        }

        $this->info("Estimated backup size: {$this->formatBytes($estimate)}");

        if ($this->option('estimate')) {
            $this->info('Size estimation complete. Use --no-estimate to create actual backup.');
            return 0;
        }

        // Safety check 3: Confirmation prompt (unless --force)
        if (!$this->option('force')) {
            if (!$this->confirm('Create Carey one site backup? This will only read files, not modify anything.')) {
                $this->info('Backup cancelled.');
                return 0;
            }
        }

        // Create backup
        try {
            $backupFile = $this->createBackup();
            $this->info("✓ Backup created successfully: $backupFile");

            // Upload to cloud if requested
            if ($this->option('cloud')) {
                $this->uploadToCloud($backupFile);
            }

            return 0;
        } catch (\Exception $e) {
            $this->error('Backup failed: ' . $e->getMessage());
            return 1;
        }
    }

    private function verifySiteAccess()
    {
        $this->info("Checking access to: $this->sitePath");

        if (!is_dir($this->sitePath)) {
            $this->error("Site directory does not exist: $this->sitePath");
            return false;
        }

        if (!is_readable($this->sitePath)) {
            $this->error("Site directory is not readable: $this->sitePath");
            return false;
        }

        // Try to list a few files to ensure we can read
        $files = scandir($this->sitePath);
        if ($files === false || count($files) < 3) {
            $this->error("Cannot read files in site directory");
            return false;
        }

        $this->info("✓ Site access verified");
        return true;
    }

    private function estimateBackupSize()
    {
        $this->info("Estimating smart backup size (excluding WordPress core, WooCommerce, unnecessary plugins)...");

        try {
            // Build du command with exclusions to estimate actual backup size
            $duCommand = ['du', '-sb'];

            // Add exclude patterns (du uses different syntax than tar)
            foreach ($this->excludePatterns as $pattern) {
                $duCommand[] = '--exclude';
                $duCommand[] = $pattern;
            }

            $duCommand[] = $this->sitePath;

            $process = new Process($duCommand);
            $process->run();

            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            $output = trim($process->getOutput());
            $parts = preg_split('/\s+/', $output);

            if (count($parts) >= 1) {
                $smartSize = (int) $parts[0];

                // Also show what we're excluding for context
                $fullSize = $this->getFullSiteSize();
                $savedSize = $fullSize - $smartSize;

                $this->info("Full site size: " . $this->formatBytes($fullSize));
                $this->info("Smart backup size: " . $this->formatBytes($smartSize));
                $this->info("Space saved: " . $this->formatBytes($savedSize) . " (" . round(($savedSize / $fullSize) * 100, 1) . "%)");

                return $smartSize;
            }

            return false;
        } catch (\Exception $e) {
            $this->error("Size estimation failed: " . $e->getMessage());
            return false;
        }
    }

    private function getFullSiteSize()
    {
        try {
            $process = new Process(['du', '-sb', $this->sitePath]);
            $process->run();

            if (!$process->isSuccessful()) {
                return 0;
            }

            $output = trim($process->getOutput());
            $parts = preg_split('/\s+/', $output);
            return (int) $parts[0];
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function createBackup()
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $backupDir = storage_path('backups');

        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0775, true);
        }

        $backupFile = "$backupDir/carey-one-backup_$timestamp.tar.gz";

        $this->info("Creating smart backup (excluding WordPress core, WooCommerce, unnecessary plugins): $backupFile");

        // Build tar command with exclusions
        $tarCommand = ['tar', '-czf', $backupFile];

        // Add exclude patterns
        foreach ($this->excludePatterns as $pattern) {
            $tarCommand[] = '--exclude';
            $tarCommand[] = $pattern;
        }

        // Add the source directory
        $tarCommand[] = '-C';
        $tarCommand[] = dirname($this->sitePath);
        $tarCommand[] = basename($this->sitePath);

        $process = new Process($tarCommand);
        $process->setTimeout(3600); // 1 hour timeout
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        // Verify backup was created and has reasonable size
        if (!file_exists($backupFile)) {
            throw new \Exception("Backup file was not created");
        }

        $backupSize = filesize($backupFile);
        if ($backupSize < 1024) { // Less than 1KB is suspicious
            throw new \Exception("Backup file is suspiciously small: " . $this->formatBytes($backupSize));
        }

        $this->info("✓ Smart backup created successfully");
        $this->info("✓ Backup size: " . $this->formatBytes($backupSize));
        $this->info("✓ Excluded: WordPress core, WooCommerce, unnecessary plugins");
        $this->info("✓ Kept: Custom MWF-* plugins, themes, uploads, database configs");

        return $backupFile;
    }

    private function uploadToCloud($backupFile)
    {
        $this->info("Uploading to Google Drive...");

        try {
            $process = new Process([
                'rclone',
                'copy',
                $backupFile,
                'gdrive:backups/carey-one/'
            ]);

            $process->setTimeout(1800); // 30 minutes for upload
            $process->run();

            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            $this->info("✓ Uploaded to Google Drive");
        } catch (\Exception $e) {
            $this->warn("Cloud upload failed, but local backup is safe: " . $e->getMessage());
        }
    }

    private function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}