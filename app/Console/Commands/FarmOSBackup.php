<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class FarmOSBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:farmos {--estimate : Show size estimate without creating backup} {--force : Skip confirmation prompt} {--cloud : Upload backup to Google Drive after creation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a smart backup of farmOS excluding massive core/vendor directories';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== FARMOS SMART BACKUP ===');

        // Define what to backup (only essential farmOS data)
        $farmOSPath = '/var/www/vhosts/middleworldfarms.org/subdomains/farmos';
        $includePaths = [
            'composer.json' => $farmOSPath . '/composer.json',
            'composer.lock' => $farmOSPath . '/composer.lock',
            'web/autoload.php' => $farmOSPath . '/web/autoload.php',
            'web/index.php' => $farmOSPath . '/web/index.php',
            'web/.htaccess' => $farmOSPath . '/web/.htaccess',
            'web/.ht.router.php' => $farmOSPath . '/web/.ht.router.php',
            'web/sites/default/settings.php' => $farmOSPath . '/web/sites/default/settings.php',
            'web/sites/default/default.services.yml' => $farmOSPath . '/web/sites/default/default.services.yml',
            'web/sites/default/config' => $farmOSPath . '/web/sites/default/config',
            'web/sites/default/files' => $farmOSPath . '/web/sites/default/files',
            'web/modules/custom' => $farmOSPath . '/web/modules/custom',
            'web/libraries' => $farmOSPath . '/web/libraries',
            'web/profiles' => $farmOSPath . '/web/profiles',
        ];

        // Calculate total size
        $totalSize = 0;
        $this->info('Calculating sizes...');

        foreach ($includePaths as $name => $path) {
            if (file_exists($path)) {
                $size = $this->getDirectorySize($path);
                $totalSize += $size;
                $this->line("  $name: " . $this->formatBytes($size));
            } else {
                $this->warn("  $name: NOT FOUND");
            }
        }

        $this->info("Total estimated size: " . $this->formatBytes($totalSize));
        $this->warn("Excluded: core/ (~148MB), vendor/ (~127MB), contrib modules (~50MB+)");
        $this->info("Space saved: ~325MB+ by excluding Drupal core and dependencies");

        if ($this->option('estimate')) {
            $this->info('Use --no-estimate to actually create the backup');
            return;
        }

        if (!$this->option('estimate')) {
            if (!$this->option('force') && $this->input->isInteractive()) {
                if (!$this->confirm('Create farmOS backup? This may take 3-5 minutes.')) {
                    return;
                }
            }
        }

        // Create backup
        $timestamp = date('Y-m-d_H-i-s');
        $backupDir = storage_path('backups');
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0775, true);
            chown($backupDir, 'root');
            chgrp($backupDir, 'www-data');
        }

        $backupFile = "$backupDir/farmos-backup_$timestamp.zip";

        $this->info("Creating backup: $backupFile");
        $this->info('This will take 3-5 minutes...');

        $zip = new ZipArchive();
        if ($zip->open($backupFile, ZipArchive::CREATE) !== TRUE) {
            $this->error('Cannot create backup file');
            return;
        }

        $fileCount = 0;
        foreach ($includePaths as $name => $path) {
            if (file_exists($path)) {
                $this->info("Adding $name...");
                $this->addToZip($zip, $path, $name, $fileCount);
            }
        }

        // Add farmOS database dump
        $this->info("Backing up farmOS database...");
        $dbBackupPath = storage_path('app/db-backup-temp');
        if (!is_dir($dbBackupPath)) {
            mkdir($dbBackupPath, 0700, true);
        }

        $timestamp = date('Y-m-d_H-i-s');
        $dumpFile = "$dbBackupPath/farmos_db_$timestamp.sql";
        $this->info("  Dumping farmos_db...");
        
        // Use root credentials since this runs as root and farmos user doesn't have remote access
        $command = sprintf(
            "mysqldump --single-transaction --quick %s > %s 2>&1",
            escapeshellarg('farmos_db'),
            escapeshellarg($dumpFile)
        );
        
        exec($command, $output, $returnCode);
        
        if ($returnCode === 0 && file_exists($dumpFile)) {
            $zip->addFile($dumpFile, "database/farmos_db.sql");
            $fileCount++;
            $this->info("  ✅ Database backed up: " . $this->formatBytes(filesize($dumpFile)));
        } else {
            $this->warn("  ⚠️  Failed to backup farmOS database");
        }

        $zip->close();

        // Cleanup temporary db dump
        if (file_exists($dumpFile)) {
            unlink($dumpFile);
        }
        if (is_dir($dbBackupPath) && count(scandir($dbBackupPath)) === 2) {
            rmdir($dbBackupPath);
        }

        // Set proper permissions for web server access
        chmod($backupFile, 0664);

        $actualSize = filesize($backupFile);
        $this->info('✅ FarmOS backup completed!');
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
