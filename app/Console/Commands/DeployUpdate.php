<?php

namespace App\Console\Commands;

use App\Models\UpdateTracking;
use App\Services\UpdateTrackingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class DeployUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deploy:update
                            {version : The update version to deploy}
                            {environment : Target environment (staging/production)}
                            {--skip-tests : Skip running tests}
                            {--skip-backup : Skip database backup}
                            {--dry-run : Show what would be done without executing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deploy a specific update version to target environment';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $version = $this->argument('version');
        $environment = $this->argument('environment');
        $skipTests = $this->option('skip-tests');
        $skipBackup = $this->option('skip-backup');
        $dryRun = $this->option('dry-run');

        $this->info("🚀 Deploying update {$version} to {$environment} environment");

        if ($dryRun) {
            $this->warn("🔍 DRY RUN MODE - No changes will be made");
        }

        // Validate environment
        if (!in_array($environment, ['staging', 'production'])) {
            $this->error("❌ Invalid environment. Must be 'staging' or 'production'");
            return 1;
        }

        // Find the update
        $update = UpdateTracking::where('version', $version)
            ->where('environment', 'demo') // Updates are logged in staging first
            ->first();

        if (!$update) {
            $this->error("❌ Update {$version} not found in staging environment");
            return 1;
        }

        $this->info("📋 Update Details:");
        $this->line("   Title: {$update->title}");
        $this->line("   Description: {$update->description}");
        $this->line("   Files Changed: " . count($update->files_changed));
        $this->line("   Applied At: {$update->applied_at->format('Y-m-d H:i:s')}");

        if (!$this->confirm('Do you want to proceed with this deployment?', true)) {
            $this->info("❌ Deployment cancelled");
            return 0;
        }

        try {
            // Step 1: Create backup
            if (!$skipBackup && !$dryRun) {
                $this->info("💾 Creating database backup...");
                Artisan::call('backup:run', ['--only-db' => true]);
                $this->info("✅ Backup created");
            }

            // Step 2: Run tests (if not skipped)
            if (!$skipTests && !$dryRun) {
                $this->info("🧪 Running tests...");
                $exitCode = Artisan::call('test');
                if ($exitCode !== 0) {
                    $this->error("❌ Tests failed. Aborting deployment.");
                    return 1;
                }
                $this->info("✅ Tests passed");
            }

            // Step 3: Run migrations (if any migration files changed)
            $hasMigrations = collect($update->files_changed)
                ->contains(fn($file) => str_contains($file, 'database/migrations/'));

            if ($hasMigrations && !$dryRun) {
                $this->info("🗄️ Running database migrations...");
                Artisan::call('migrate');
                $this->info("✅ Migrations completed");
            }

            // Step 4: Clear caches
            if (!$dryRun) {
                $this->info("🧹 Clearing caches...");
                Artisan::call('config:clear');
                Artisan::call('route:clear');
                Artisan::call('view:clear');
                Artisan::call('cache:clear');
                $this->info("✅ Caches cleared");
            }

            // Step 5: Optimize application
            if (!$dryRun) {
                $this->info("⚡ Optimizing application...");
                Artisan::call('config:cache');
                Artisan::call('route:cache');
                Artisan::call('view:cache');
                Artisan::call('optimize');
                $this->info("✅ Application optimized");
            }

            // Step 6: Log deployment
            if (!$dryRun) {
                UpdateTrackingService::logCodeChange(
                    $version,
                    $update->title,
                    "Deployed to {$environment} environment via CLI",
                    $update->files_changed,
                    null,
                    $environment
                );
                $this->info("📝 Deployment logged");
            }

            $this->success("🎉 Update {$version} successfully deployed to {$environment}!");

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Deployment failed: " . $e->getMessage());

            // Attempt rollback
            if (!$dryRun && !$skipBackup) {
                $this->warn("🔄 Attempting rollback...");
                try {
                    // This would need more sophisticated rollback logic
                    $this->warn("⚠️ Manual rollback may be required");
                } catch (\Exception $rollbackError) {
                    $this->error("❌ Rollback also failed: " . $rollbackError->getMessage());
                }
            }

            return 1;
        }
    }
}
