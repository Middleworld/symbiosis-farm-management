<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SubscriptionAuditService;
use Illuminate\Support\Facades\Log;

class AuditSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:audit {--send-alerts : Send alerts for critical issues}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Audit WooCommerce subscriptions for billing issues and revenue loss';

    protected $auditService;

    /**
     * Create a new command instance.
     */
    public function __construct(SubscriptionAuditService $auditService)
    {
        parent::__construct();
        $this->auditService = $auditService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Starting subscription audit...');

        try {
            // Perform the audit
            $report = $this->auditService->performAudit();

            if (isset($report['error'])) {
                $this->error('❌ Audit failed: ' . $report['error']);
                return 1;
            }

            // Display results
            $this->displayAuditResults($report);

            // Send alerts if requested and issues found
            if ($this->option('send-alerts')) {
                $this->auditService->sendAlertIfNeeded($report);
                $this->info('📧 Alerts sent for critical issues');
            }

            // Save report to storage
            $this->saveAuditReport($report);

            $this->info('✅ Subscription audit completed successfully');

            // Return exit code based on findings
            $brokenCount = $report['data']['broken_count'] ?? 0;
            return $brokenCount > 0 ? 1 : 0; // Non-zero exit code if issues found

        } catch (\Exception $e) {
            $this->error('❌ Audit failed with exception: ' . $e->getMessage());
            Log::error('Subscription audit command failed', ['error' => $e->getMessage()]);
            return 1;
        }
    }

    /**
     * Display audit results in a nice format
     */
    private function displayAuditResults(array $report): void
    {
        $data = $report['data'];

        $this->line('');
        $this->info('📊 Audit Results:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Subscriptions', $data['total_subscriptions'] ?? 0],
                ['With Scheduling', $data['with_scheduling'] ?? 0],
                ['Broken Subscriptions', $data['broken_count'] ?? 0],
                ['Affected Customers', $data['affected_customers'] ?? 0],
                ['Estimated Loss', '£' . number_format($data['estimated_loss'] ?? 0, 2)],
                ['Audit Date', $data['audit_date'] ?? 'Unknown']
            ]
        );

        // Show broken subscriptions if any
        if (!empty($data['broken_subscriptions'])) {
            $this->line('');
            $this->warn('🚨 Broken Subscriptions:');
            $brokenTable = [];
            foreach ($data['broken_subscriptions'] as $sub) {
                $brokenTable[] = [
                    $sub['id'],
                    $sub['customer'],
                    '£' . number_format($sub['amount'], 2),
                    $sub['billing_period'] . 'ly',
                    $sub['missed_payments'],
                    '£' . number_format($sub['estimated_loss'], 2)
                ];
            }
            $this->table(
                ['ID', 'Customer', 'Amount', 'Frequency', 'Missed Payments', 'Est. Loss'],
                $brokenTable
            );
        }

        // Show AI analysis if available
        if (!empty($report['analysis'])) {
            $this->line('');
            $this->info('🤖 AI Analysis:');
            $this->line($report['analysis']['summary'] ?? 'No summary available');
        }

        // Show recommendations
        if (!empty($report['recommendations'])) {
            $this->line('');
            $this->info('💡 Recommendations:');
            foreach ($report['recommendations'] as $rec) {
                $priority = match($rec['priority']) {
                    'CRITICAL' => '🔴',
                    'HIGH' => '🟠',
                    'MEDIUM' => '🟡',
                    'LOW' => '🟢',
                    default => '⚪'
                };
                $this->line("{$priority} {$rec['action']}: {$rec['description']}");
                $this->line("   Impact: {$rec['impact']}");
                $this->line('');
            }
        }
    }

    /**
     * Save audit report to storage
     */
    private function saveAuditReport(array $report): void
    {
        $filename = 'subscription-audit-' . now()->format('Y-m-d-H-i-s') . '.json';
        $path = storage_path('audits/' . $filename);

        // Ensure directory exists
        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($path, json_encode($report, JSON_PRETTY_PRINT));
        $this->info("💾 Report saved to: storage/audits/{$filename}");
    }
}
