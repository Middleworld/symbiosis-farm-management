<?php

namespace App\Services;

use App\Models\UpdateTracking;
use Illuminate\Support\Facades\Auth;

class UpdateTrackingService
{
    /**
     * Log an update to the tracking system
     */
    public static function logUpdate(
        string $version,
        string $title,
        string $description,
        array $filesChanged = [],
        array $changes = [],
        ?string $customerId = null,
        string $environment = 'production',
        ?string $appliedBy = null
    ): UpdateTracking {
        return UpdateTracking::create([
            'version' => $version,
            'title' => $title,
            'description' => $description,
            'files_changed' => $filesChanged,
            'changes' => $changes,
            'customer_id' => $customerId,
            'environment' => $environment,
            'applied_at' => now(),
            'applied_by' => $appliedBy ?? (Auth::check() ? Auth::user()->email : 'system')
        ]);
    }

    /**
     * Log a code change with automatic file detection
     */
    public static function logCodeChange(
        string $version,
        string $title,
        string $description,
        array $filesChanged,
        ?string $customerId = null,
        string $environment = 'production'
    ): UpdateTracking {
        // Generate detailed changes from files
        $changes = [];
        foreach ($filesChanged as $file) {
            $changes[] = [
                'file' => $file,
                'type' => self::detectChangeType($file),
                'description' => "Modified {$file}"
            ];
        }

        return self::logUpdate(
            $version,
            $title,
            $description,
            $filesChanged,
            $changes,
            $customerId,
            $environment
        );
    }

    /**
     * Detect the type of change based on file path
     */
    private static function detectChangeType(string $file): string
    {
        if (str_contains($file, 'database/migrations/')) {
            return 'migration';
        }
        if (str_contains($file, 'app/Models/')) {
            return 'model';
        }
        if (str_contains($file, 'app/Http/Controllers/')) {
            return 'controller';
        }
        if (str_contains($file, 'resources/views/')) {
            return 'view';
        }
        if (str_contains($file, 'routes/')) {
            return 'route';
        }
        if (str_contains($file, 'app/Services/')) {
            return 'service';
        }
        if (str_contains($file, 'config/')) {
            return 'config';
        }
        
        return 'other';
    }

    /**
     * Get update history for a customer
     */
    public static function getUpdateHistory(?string $customerId = null, string $environment = 'production'): \Illuminate\Database\Eloquent\Collection
    {
        $query = UpdateTracking::inEnvironment($environment)
            ->orderBy('applied_at', 'desc');
            
        if ($customerId) {
            $query->forCustomer($customerId);
        }
        
        return $query->get();
    }

    /**
     * Generate update script for a customer
     */
    public static function generateUpdateScript(string $customerId, string $targetVersion): string
    {
        $updates = UpdateTracking::forCustomer($customerId)
            ->where('version', '<=', $targetVersion)
            ->orderBy('version')
            ->get();

        $script = "#!/bin/bash\n";
        $script .= "# Update script for Symbiosis customer: {$customerId}\n";
        $script .= "# Target version: {$targetVersion}\n";
        $script .= "# Generated: " . now()->format('Y-m-d H:i:s') . "\n\n";

        foreach ($updates as $update) {
            $script .= "# {$update->version}: {$update->title}\n";
            $script .= "# {$update->description}\n";
            
            foreach ($update->changes as $change) {
                $script .= "# - {$change['description']}\n";
            }
            
            $script .= "\n";
            // Add specific commands based on change type
            foreach ($update->changes as $change) {
                switch ($change['type']) {
                    case 'migration':
                        $script .= "php artisan migrate\n";
                        break;
                    case 'config':
                        $script .= "php artisan config:cache\n";
                        break;
                    case 'route':
                        $script .= "php artisan route:cache\n";
                        break;
                }
            }
            
            $script .= "echo \"Applied update {$update->version}\"\n\n";
        }

        return $script;
    }

    /**
     * Check if an update is ready for deployment to production
     */
    public static function isUpdateReadyForProduction(string $version): bool
    {
        // Check if update exists in staging
        $stagingUpdate = self::getUpdateByVersion($version, 'demo');
        if (!$stagingUpdate) {
            return false;
        }

        // Check if update already exists in production
        $productionUpdate = self::getUpdateByVersion($version, 'production');
        if ($productionUpdate) {
            return false; // Already deployed
        }

        // Additional checks could be added here:
        // - Code review approval
        // - Test coverage
        // - Security scan results

        return true;
    }

    /**
     * Get update by version and environment
     */
    public static function getUpdateByVersion(string $version, string $environment): ?UpdateTracking
    {
        return UpdateTracking::where('version', $version)
            ->where('environment', $environment)
            ->first();
    }

    /**
     * Get deployment history for an environment
     */
    public static function getDeploymentHistory(string $environment, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return UpdateTracking::where('environment', $environment)
            ->orderBy('applied_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Generate deployment report
     */
    public static function generateDeploymentReport(string $environment, string $startDate = null, string $endDate = null): array
    {
        $query = UpdateTracking::where('environment', $environment);

        if ($startDate) {
            $query->where('applied_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('applied_at', '<=', $endDate);
        }

        $updates = $query->orderBy('applied_at', 'desc')->get();

        return [
            'total_deployments' => $updates->count(),
            'date_range' => [
                'start' => $startDate ?? $updates->last()?->applied_at?->format('Y-m-d'),
                'end' => $endDate ?? $updates->first()?->applied_at?->format('Y-m-d'),
            ],
            'updates' => $updates->map(function ($update) {
                return [
                    'version' => $update->version,
                    'title' => $update->title,
                    'applied_at' => $update->applied_at->format('Y-m-d H:i:s'),
                    'files_changed_count' => count($update->files_changed),
                    'applied_by' => $update->applied_by,
                ];
            }),
            'summary' => [
                'total_files_changed' => $updates->sum(fn($u) => count($u->files_changed)),
                'most_active_developer' => $updates->groupBy('applied_by')
                    ->map->count()
                    ->sortDesc()
                    ->keys()
                    ->first(),
            ]
        ];
    }

    /**
     * Validate deployment prerequisites
     */
    public static function validateDeploymentPrerequisites(string $version, string $targetEnvironment): array
    {
        $issues = [];

        // Check if update exists in staging
        $stagingUpdate = self::getUpdateByVersion($version, 'demo');
        if (!$stagingUpdate) {
            $issues[] = "Update {$version} not found in staging environment";
        }

        // Check if already deployed
        $existingUpdate = self::getUpdateByVersion($version, $targetEnvironment);
        if ($existingUpdate) {
            $issues[] = "Update {$version} already deployed to {$targetEnvironment}";
        }

        // Check for missing dependencies (this could be enhanced)
        if ($stagingUpdate && !empty($stagingUpdate->files_changed)) {
            foreach ($stagingUpdate->files_changed as $file) {
                if (!file_exists(base_path($file))) {
                    $issues[] = "Required file missing: {$file}";
                }
            }
        }

        return $issues;
    }