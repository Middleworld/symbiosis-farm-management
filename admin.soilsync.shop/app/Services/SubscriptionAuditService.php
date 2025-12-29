<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\MistralService;

class SubscriptionAuditService
{
    protected $mistral;

    public function __construct(MistralService $mistral)
    {
        $this->mistral = $mistral;
    }

    /**
     * Perform complete subscription audit
     */
    public function performAudit(): array
    {
        Log::info('Starting subscription audit');

        try {
            // Gather subscription data
            $subscriptionData = $this->gatherSubscriptionData();
            
            // Analyze with AI
            $analysis = $this->mistral->analyzeSubscriptions($subscriptionData);
            
            // Generate report
            $report = [
                'timestamp' => now(),
                'data' => $subscriptionData,
                'analysis' => $analysis,
                'recommendations' => $this->generateActionPlan($subscriptionData, $analysis)
            ];

            Log::info('Subscription audit completed', [
                'total_subscriptions' => $subscriptionData['total_subscriptions'] ?? 0,
                'broken_count' => $subscriptionData['broken_count'] ?? 0,
                'estimated_loss' => $subscriptionData['estimated_loss'] ?? 0
            ]);

            return $report;

        } catch (\Exception $e) {
            Log::error('Subscription audit failed', ['error' => $e->getMessage()]);
            return [
                'error' => 'Audit failed: ' . $e->getMessage(),
                'timestamp' => now()
            ];
        }
    }

    /**
     * Gather comprehensive subscription data
     */
    private function gatherSubscriptionData(): array
    {
        // Get active subscriptions
        $activeSubs = DB::connection('wordpress')
            ->select('SELECT ID, post_date FROM D6sPMX_posts WHERE post_type = "shop_subscription" AND post_status = "wc-active"');

        $activeIds = array_column($activeSubs, 'ID');

        // Get subscriptions with scheduled renewals
        $withActions = DB::connection('wordpress')
            ->select('SELECT DISTINCT CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(args, \'"subscription_id":\', -1), \'}\', 1) AS UNSIGNED) as sub_id FROM D6sPMX_actionscheduler_actions WHERE hook = "woocommerce_scheduled_subscription_payment" AND status = "pending"');

        $scheduledIds = array_column($withActions, 'sub_id');

        // Find broken subscriptions
        $brokenIds = array_diff($activeIds, $scheduledIds);
        $brokenSubscriptions = [];

        foreach ($brokenIds as $id) {
            $subData = $this->getSubscriptionDetails($id);
            if ($subData) {
                $brokenSubscriptions[] = $subData;
            }
        }

        // Calculate revenue impact
        $estimatedLoss = $this->calculateRevenueImpact($brokenSubscriptions);

        return [
            'total_subscriptions' => count($activeIds),
            'with_scheduling' => count($scheduledIds),
            'broken_count' => count($brokenIds),
            'broken_subscriptions' => $brokenSubscriptions,
            'estimated_loss' => $estimatedLoss,
            'affected_customers' => count($brokenSubscriptions),
            'audit_date' => now()->toDateTimeString()
        ];
    }

    /**
     * Get detailed subscription information
     */
    private function getSubscriptionDetails(int $subscriptionId): ?array
    {
        try {
            // Get subscription post data
            $sub = DB::connection('wordpress')
                ->select('SELECT post_date FROM D6sPMX_posts WHERE ID = ? AND post_type = "shop_subscription"', [$subscriptionId]);

            if (empty($sub)) {
                return null;
            }

            // Get subscription meta data
            $meta = DB::connection('wordpress')
                ->select('SELECT meta_key, meta_value FROM D6sPMX_postmeta WHERE post_id = ?', [$subscriptionId]);

            $billingPeriod = 'unknown';
            $billingInterval = 1;
            $total = 0;
            $customerName = 'Unknown';

            foreach ($meta as $item) {
                switch ($item->meta_key) {
                    case '_billing_period':
                        $billingPeriod = $item->meta_value;
                        break;
                    case '_billing_interval':
                        $billingInterval = (int) $item->meta_value;
                        break;
                    case '_order_total':
                        $total = (float) $item->meta_value;
                        break;
                    case '_billing_first_name':
                        $customerName = $item->meta_value;
                        break;
                    case '_billing_last_name':
                        $customerName .= ' ' . $item->meta_value;
                        break;
                }
            }

            // Calculate missed payments
            $created = strtotime($sub[0]->post_date);
            $now = time();
            $missedPayments = 0;

            if ($billingPeriod === 'week') {
                $interval = $billingInterval * 604800; // seconds in week
            } elseif ($billingPeriod === 'month') {
                $interval = $billingInterval * 2635200; // approx seconds in month
            } else {
                $interval = 604800; // default to weekly
            }

            for ($date = $created + $interval; $date <= $now; $date += $interval) {
                $missedPayments++;
            }

            return [
                'id' => $subscriptionId,
                'created' => $sub[0]->post_date,
                'customer' => trim($customerName),
                'amount' => $total,
                'billing_period' => $billingPeriod,
                'billing_interval' => $billingInterval,
                'missed_payments' => $missedPayments,
                'estimated_loss' => $missedPayments * $total
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get subscription details', ['id' => $subscriptionId, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Calculate total revenue impact
     */
    private function calculateRevenueImpact(array $brokenSubscriptions): float
    {
        $total = 0;
        foreach ($brokenSubscriptions as $sub) {
            $total += $sub['estimated_loss'] ?? 0;
        }
        return $total;
    }

    /**
     * Generate action plan based on analysis
     */
    private function generateActionPlan(array $data, array $analysis): array
    {
        $actions = [];

        if (($data['broken_count'] ?? 0) > 0) {
            $actions[] = [
                'priority' => 'CRITICAL',
                'action' => 'Fix broken subscription scheduling',
                'description' => "Create renewal actions for {$data['broken_count']} subscriptions",
                'impact' => "Recover £" . number_format($data['estimated_loss'], 2) . ' in lost revenue'
            ];

            $actions[] = [
                'priority' => 'HIGH',
                'action' => 'Contact affected customers',
                'description' => "Inform {$data['affected_customers']} customers about billing issues",
                'impact' => 'Maintain customer trust and recover payments'
            ];
        }

        $actions[] = [
            'priority' => 'MEDIUM',
            'action' => 'Implement monitoring system',
            'description' => 'Set up daily subscription audits with AI analysis',
            'impact' => 'Prevent future revenue loss'
        ];

        $actions[] = [
            'priority' => 'LOW',
            'action' => 'Review WooCommerce Subscriptions plugin',
            'description' => 'Check for updates and configuration issues',
            'impact' => 'Improve system reliability'
        ];

        return $actions;
    }

    /**
     * Send alert if critical issues found
     */
    public function sendAlertIfNeeded(array $auditReport): void
    {
        $criticalIssues = $auditReport['data']['broken_count'] ?? 0;
        
        if ($criticalIssues > 0) {
            Log::critical('CRITICAL: Subscription audit found issues', [
                'broken_subscriptions' => $criticalIssues,
                'estimated_loss' => $auditReport['data']['estimated_loss'] ?? 0,
                'affected_customers' => $auditReport['data']['affected_customers'] ?? 0
            ]);

            // Here you could add email/Slack notifications
            // For now, just log it
        }
    }
}
