<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class BackupRAGData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rag:backup {--cleanup-days=30 : Delete backups older than this many days}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a separate backup of RAG data files (uploads, embeddings, etc.)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting RAG data backup...');

        $ragPath = storage_path('app/private/public/rag-uploads');
        $backupPath = storage_path('app/rag-backups');

        // Ensure backup directory exists
        if (!File::exists($backupPath)) {
            File::makeDirectory($backupPath, 0755, true);
        }

        // Check if RAG data exists
        if (!File::exists($ragPath)) {
            $this->warn('RAG uploads directory not found: ' . $ragPath);
            return;
        }

        // Get directory size
        $size = $this->getDirectorySize($ragPath);
        $this->info('RAG data size: ' . $this->formatBytes($size));

        // Create backup filename
        $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
        $backupFile = $backupPath . '/rag-backup_' . $timestamp . '.tar.gz';

        $this->info('Creating backup: ' . basename($backupFile));

        // Create tar.gz archive
        $command = "cd " . escapeshellarg(dirname($ragPath)) . " && tar -czf " . escapeshellarg($backupFile) . " " . escapeshellarg(basename($ragPath));
        $result = shell_exec($command . " 2>&1");

        if (file_exists($backupFile)) {
            $backupSize = filesize($backupFile);
            $this->info('✅ RAG backup created successfully: ' . basename($backupFile));
            $this->info('Backup size: ' . $this->formatBytes($backupSize));
        } else {
            $this->error('❌ Failed to create RAG backup');
            $this->error('Command output: ' . $result);
            return;
        }

        // Cleanup old backups
        $cleanupDays = $this->option('cleanup-days');
        $this->cleanupOldBackups($backupPath, $cleanupDays);

        $this->info('RAG backup process completed!');
    }

    /**
     * Get directory size recursively
     */
    private function getDirectorySize($path)
    {
        $size = 0;
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));
        foreach ($files as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }
        return $size;
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Clean up old backup files
     */
    private function cleanupOldBackups($backupPath, $days)
    {
        $this->info("Cleaning up backups older than {$days} days...");

        $files = File::files($backupPath);
        $deleted = 0;

        foreach ($files as $file) {
            $fileAge = Carbon::createFromTimestamp($file->getMTime());
            if (Carbon::now()->diffInDays($fileAge) > $days) {
                File::delete($file->getPathname());
                $deleted++;
                $this->line('Deleted old backup: ' . $file->getFilename());
            }
        }

        if ($deleted === 0) {
            $this->info('No old backups to clean up.');
        } else {
            $this->info("Cleaned up {$deleted} old backup(s).");
        }
    }
}
