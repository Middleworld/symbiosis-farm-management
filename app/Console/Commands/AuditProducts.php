<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ProductAuditService;
use Illuminate\Support\Facades\Log;

class AuditProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:audit {--send-alerts : Send alerts for critical issues}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Audit WooCommerce products for configuration issues and subscription problems';

    protected $auditService;

    /**
     * Create a new command instance.
     */
    public function __construct(ProductAuditService $auditService)
    {
        parent::__construct();
        $this->auditService = $auditService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Starting product audit...');

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

            $this->info('✅ Product audit completed successfully');

            // Return exit code based on findings
            $issuesCount = $report['data']['issues_count'] ?? 0;
            return $issuesCount > 0 ? 1 : 0; // Non-zero exit code if issues found

        } catch (\Exception $e) {
            $this->error('❌ Audit failed with exception: ' . $e->getMessage());
            Log::error('Product audit command failed', ['error' => $e->getMessage()]);
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
        $this->info('📊 Product Audit Results:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Products', $data['total_products'] ?? 0],
                ['Subscription Products', $data['subscription_products'] ?? 0],
                ['Issues Found', $data['issues_count'] ?? 0],
                ['Audit Date', $data['audit_date'] ?? 'Unknown']
            ]
        );

        // Show issues if any
        if (!empty($data['issues'])) {
            $this->line('');
            $this->warn('🚨 Product Issues:');
            $issuesTable = [];
            foreach ($data['issues'] as $issue) {
                $severity = match($issue['severity']) {
                    'CRITICAL' => '🔴',
                    'HIGH' => '🟠',
                    'MEDIUM' => '🟡',
                    'LOW' => '🟢',
                    default => '⚪'
                };
                $issuesTable[] = [
                    $severity,
                    $issue['type'],
                    $issue['product_name'],
                    $issue['description']
                ];
            }
            $this->table(
                ['Severity', 'Type', 'Product', 'Description'],
                $issuesTable
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
                    'CRITICAL' => '��',
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
        $filename = 'product-audit-' . now()->format('Y-m-d-H-i-s') . '.json';
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
