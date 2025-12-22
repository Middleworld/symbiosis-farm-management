<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class CustomBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:custom {--site=admin : Site identifier for the backup} {--estimate : Show size estimate without creating backup} {--force : Skip confirmation prompt} {--cloud : Upload backup to Google Drive after creation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a custom backup of only essential application code';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== CUSTOM BACKUP ===');

        // Define what to backup (only custom code)
        $includePaths = [
            'app' => base_path('app'),
            'config' => base_path('config'),
            'database' => base_path('database'),
            'resources' => base_path('resources'),
            'routes' => base_path('routes'),
            'public' => base_path('public'),
            'docs' => base_path('docs'),
            'data' => base_path('data'),
            'assets' => base_path('assets'),
            'composer.json' => base_path('composer.json'),
            'package.json' => base_path('package.json'),
            'artisan' => base_path('artisan'),
            'README.md' => base_path('README.md'),
        ];

        // Calculate total size
        $totalSize = 0;
        $this->info('Calculating sizes...');

        foreach ($includePaths as $name => $path) {
            if (file_exists($path)) {
                $size = $this->getDirectorySize($path);
                $totalSize += $size;
                $this->line("  $name: " . $this->formatBytes($size));
            }
        }

        $this->info("Total estimated size: " . $this->formatBytes($totalSize));
        $this->warn("Excluded: vendor, node_modules, ai_service, storage, .git (~14GB total)");

        if ($this->option('estimate')) {
            $this->info('Use --no-estimate to actually create the backup');
            return;
        }

        if (!$this->option('estimate')) {
            // Skip confirmation if --force flag is used or if not running interactively
            if (!$this->option('force') && $this->input->isInteractive()) {
                if (!$this->confirm('Create backup? This will take 2-5 minutes.')) {
                    return;
                }
            }
        }

        // Create backup
        $site = $this->option('site');

        // Map full site keys to shorter identifiers for filenames
        $siteMapping = [
            'admin.middleworldfarms.org' => 'admin',
            'farmos.middleworldfarms.org' => 'farmos',
            'middleworldfarms.org' => 'middleworldfarms',
            'middleworld.farm' => 'middleworld',
        ];

        $filenameSite = $siteMapping[$site] ?? $site;
        $timestamp = date('Y-m-d_H-i-s');
        $backupDir = storage_path('backups');
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0775, true);
            chown($backupDir, 'root');
            chgrp($backupDir, 'www-data');
        }

        $backupFile = "$backupDir/{$filenameSite}-backup_$timestamp.zip";

        $this->info("Creating backup: $backupFile");
        $this->info('This will take 2-5 minutes...');

        $zip = new ZipArchive();
        if ($zip->open($backupFile, ZipArchive::CREATE) !== TRUE) {
            $this->error('Cannot create backup file');
            return;
        }

        $fileCount = 0;
        foreach ($includePaths as $name => $path) {
            if (file_exists($path)) {
                $this->info("Adding $name...");
                $this->addToZip($zip, $path, basename($path), $fileCount);
            }
        }

        // Add database dump (admin_db only)
        $this->info("Backing up admin database...");
        $dbBackupPath = storage_path('app/db-backup-temp');
        if (!is_dir($dbBackupPath)) {
            mkdir($dbBackupPath, 0700, true);
        }

        $dumpFile = "$dbBackupPath/admin_db_$timestamp.sql";
        $this->info("  Dumping admin_db...");
        
        // Use Laravel's database credentials from config
        $dbHost = config('database.connections.mysql.host');
        $dbUser = config('database.connections.mysql.username');
        $dbPass = config('database.connections.mysql.password');
        $dbName = config('database.connections.mysql.database');
        
        $command = sprintf(
            "mysqldump --host=%s --user=%s --password=%s --single-transaction --quick %s > %s 2>&1",
            escapeshellarg($dbHost),
            escapeshellarg($dbUser),
            escapeshellarg($dbPass),
            escapeshellarg($dbName),
            escapeshellarg($dumpFile)
        );
        
        exec($command, $output, $returnCode);
        
        if ($returnCode === 0 && file_exists($dumpFile)) {
            $zip->addFile($dumpFile, "database/admin_db.sql");
            $fileCount++;
            $this->info("  ✅ Database backed up: " . $this->formatBytes(filesize($dumpFile)));
        } else {
            $this->warn("  ⚠️  Failed to backup admin_db");
        }

        $zip->close();

        // Cleanup temporary db dump
        if (file_exists($dumpFile)) {
            unlink($dumpFile);
        }
        if (is_dir($dbBackupPath) && count(scandir($dbBackupPath)) === 2) {
            rmdir($dbBackupPath);
        }

        // Set proper permissions for web server access (owner: read+write, group: read+write)
        chmod($backupFile, 0664);

        $actualSize = filesize($backupFile);
        $this->info('✅ Backup completed!');
        $this->info("Files backed up: $fileCount");
        $this->info("Actual size: " . $this->formatBytes($actualSize));
        $this->info("Location: $backupFile");

        // Upload to cloud if requested
        if ($this->option('cloud')) {
            $this->info('📤 Uploading to Google Drive...');
            $cloudScript = '/opt/dev-scripts/cloud-backup.sh';

            if (file_exists($cloudScript)) {
                exec($cloudScript, $output, $returnCode);

                if ($returnCode === 0) {
                    $this->info('✅ Cloud upload completed!');
                    $this->info('Backup available in Google Drive: Backups/Laravel-Admin/');
                } else {
                    $this->error('❌ Cloud upload failed with exit code: ' . $returnCode);
                    if (!empty($output)) {
                        $this->error('Output: ' . implode("\n", $output));
                    }
                }
            } else {
                $this->error('❌ Cloud upload script not found: ' . $cloudScript);
            }
        }
    }

    private function addToZip($zip, $path, $relativePath, &$fileCount)
    {
        if (is_file($path)) {
            $zip->addFile($path, $relativePath);
            $fileCount++;
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $filePath = $file->getRealPath();
                $relativeFilePath = $relativePath . '/' . substr($filePath, strlen($path) + 1);
                $zip->addFile($filePath, $relativeFilePath);
                $fileCount++;
            }
        }
    }

    private function getDirectorySize($path)
    {
        if (is_file($path)) {
            return filesize($path);
        }

        $size = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }
        return $size;
    }

    private function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 1) . $units[$i];
    }
}
