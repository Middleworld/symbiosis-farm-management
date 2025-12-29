<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SubscriptionAuditService;
use App\Services\ProductAuditService;
use Illuminate\Support\Facades\Log;

class DailyAudit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'audit:daily {--send-alerts : Send alerts for critical issues}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run complete daily audit of subscriptions and products to prevent revenue loss';

    protected $subscriptionAuditService;
    protected $productAuditService;

    /**
     * Create a new command instance.
     */
    public function __construct(
        SubscriptionAuditService $subscriptionAuditService,
        ProductAuditService $productAuditService
    ) {
        parent::__construct();
        $this->subscriptionAuditService = $subscriptionAuditService;
        $this->productAuditService = $productAuditService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $startTime = now();
        $this->info('🚀 Starting daily audit suite...');
        $this->info('⏰ Started at: ' . $startTime->format('Y-m-d H:i:s'));

        $results = [
            'start_time' => $startTime,
            'subscription_audit' => null,
            'product_audit' => null,
            'overall_status' => 'success',
            'critical_issues' => 0,
            'warnings' => 0
        ];

        try {
            // Run subscription audit
            $this->info('');
            $this->info('�� Running subscription audit...');
            $subscriptionReport = $this->subscriptionAuditService->performAudit();
            $results['subscription_audit'] = $subscriptionReport;

            if (isset($subscriptionReport['error'])) {
                $this->error('❌ Subscription audit failed: ' . $subscriptionReport['error']);
                $results['overall_status'] = 'failed';
            } else {
                $brokenCount = $subscriptionReport['data']['broken_count'] ?? 0;
                $estimatedLoss = $subscriptionReport['data']['estimated_loss'] ?? 0;

                if ($brokenCount > 0) {
                    $results['critical_issues'] += $brokenCount;
                    $this->warn("⚠️  Found {$brokenCount} broken subscriptions with £" . number_format($estimatedLoss, 2) . ' estimated loss');
                } else {
                    $this->info('✅ No subscription issues found');
                }
            }

            // Run product audit
            $this->info('');
            $this->info('📦 Running product audit...');
            $productReport = $this->productAuditService->performAudit();
            $results['product_audit'] = $productReport;

            if (isset($productReport['error'])) {
                $this->error('❌ Product audit failed: ' . $productReport['error']);
                $results['overall_status'] = 'failed';
            } else {
                $issuesCount = $productReport['data']['issues_count'] ?? 0;
                $criticalIssues = 0;
                $issues = $productReport['data']['issues'] ?? [];

                foreach ($issues as $issue) {
                    if (($issue['severity'] ?? '') === 'CRITICAL') {
                        $criticalIssues++;
                    }
                }

                if ($issuesCount > 0) {
                    $results['warnings'] += $issuesCount;
                    $results['critical_issues'] += $criticalIssues;
                    $this->warn("⚠️  Found {$issuesCount} product issues ({$criticalIssues} critical)");
                } else {
                    $this->info('✅ No product issues found');
                }
            }

            // Send alerts if requested and issues found
            if ($this->option('send-alerts') && ($results['critical_issues'] > 0 || $results['warnings'] > 0)) {
                $this->sendAlerts($results);
            }

            // Save consolidated report
            $this->saveConsolidatedReport($results);

            // Final summary
            $this->displayFinalSummary($results);

        } catch (\Exception $e) {
            $this->error('❌ Daily audit failed with exception: ' . $e->getMessage());
            Log::error('Daily audit command failed', ['error' => $e->getMessage()]);
            $results['overall_status'] = 'failed';
            $results['error'] = $e->getMessage();
            return 1;
        }

        $endTime = now();
        $duration = $endTime->diffInSeconds($startTime);
        $this->info("⏱️  Completed in {$duration} seconds");

        // Return exit code based on findings
        return ($results['critical_issues'] > 0) ? 1 : 0;
    }

    /**
     * Display subscription audit summary
     */
    private function displaySubscriptionSummary(array $report): void
    {
        $data = $report['data'];
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Subscriptions', $data['total_subscriptions'] ?? 0],
                ['Broken Subscriptions', $data['broken_count'] ?? 0],
                ['Estimated Loss', '£' . number_format($data['estimated_loss'] ?? 0, 2)]
            ]
        );
    }

    /**
     * Display product audit summary
     */
    private function displayProductSummary(array $report): void
    {
        $data = $report['data'];
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Products', $data['total_products'] ?? 0],
                ['Subscription Products', $data['subscription_products'] ?? 0],
                ['Issues Found', $data['issues_count'] ?? 0]
            ]
        );
    }

    /**
     * Send alerts for critical issues
     */
    private function sendAlerts(array $results): void
    {
        $this->info('📧 Sending alerts for detected issues...');

        // Send subscription alerts
        if ($results['subscription_audit'] && !isset($results['subscription_audit']['error'])) {
            $this->subscriptionAuditService->sendAlertIfNeeded($results['subscription_audit']);
        }

        // Send product alerts
        if ($results['product_audit'] && !isset($results['product_audit']['error'])) {
            $this->productAuditService->sendAlertIfNeeded($results['product_audit']);
        }

        $this->info('✅ Alerts sent');
    }

    /**
     * Save consolidated audit report
     */
    private function saveConsolidatedReport(array $results): void
    {
        $filename = 'daily-audit-' . now()->format('Y-m-d-H-i-s') . '.json';
        $path = storage_path('audits/' . $filename);

        // Ensure directory exists
        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($path, json_encode($results, JSON_PRETTY_PRINT));
        $this->info("💾 Consolidated report saved to: storage/audits/{$filename}");
    }

    /**
     * Display final summary
     */
    private function displayFinalSummary(array $results): void
    {
        $this->line('');
        $this->info('📊 Daily Audit Summary:');

        $status = $results['overall_status'] === 'success' ? '✅ SUCCESS' : '❌ FAILED';
        $this->line("Status: {$status}");
        $this->line("Critical Issues: {$results['critical_issues']}");
        $this->line("Warnings: {$results['warnings']}");

        if ($results['critical_issues'] > 0) {
            $this->error('🚨 ACTION REQUIRED: Critical issues detected that may cause revenue loss');
        } elseif ($results['warnings'] > 0) {
            $this->warn('⚠️  REVIEW NEEDED: Issues detected that should be addressed');
        } else {
            $this->info('🎉 ALL CLEAR: No issues detected');
        }
    }
}
