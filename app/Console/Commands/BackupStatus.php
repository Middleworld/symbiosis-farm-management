<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class BackupStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:status {--estimate : Show size estimate before backup}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show backup status and size estimates';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== BACKUP STATUS ===');

        // Show current backup disk usage
        $backupPath = storage_path('backups');
        if (is_dir($backupPath)) {
            $this->info("Backup location: $backupPath");
            $backups = glob("$backupPath/*.zip");
            $this->info('Current backups: ' . count($backups));

            if (!empty($backups)) {
                $latest = end($backups);
                $size = $this->formatBytes(filesize($latest));
                $date = date('Y-m-d H:i:s', filemtime($latest));
                $this->info("Latest backup: $size ($date)");
            }
        }

        if ($this->option('estimate')) {
            $this->showSizeEstimate();
        }

        $this->info('===================');
    }

    private function showSizeEstimate()
    {
        $this->info('=== SIZE ESTIMATE ===');

        $includePaths = [
            base_path('app'),
            base_path('config'),
            base_path('database'),
            base_path('resources'),
            base_path('routes'),
            base_path('public'),
            base_path('docs'),
            base_path('data'),
            base_path('assets'),
        ];

        $totalSize = 0;
        foreach ($includePaths as $path) {
            if (is_dir($path) || is_file($path)) {
                $size = $this->getDirectorySize($path);
                $totalSize += $size;
                $this->line("  " . basename($path) . ": " . $this->formatBytes($size));
            }
        }

        $this->info("Estimated total: " . $this->formatBytes($totalSize));
        $this->info("Expected time: 2-5 minutes");
        $this->warn("Excluded: vendor, node_modules, ai_service, storage, .git (~14GB total)");
    }

    private function getDirectorySize($path)
    {
        $size = 0;
        if (is_file($path)) {
            return filesize($path);
        }

        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));
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
