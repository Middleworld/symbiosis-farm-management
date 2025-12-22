<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SubscriptionScheduler;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FixBrokenSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:fix-broken {--subscription-id= : Fix a specific subscription} {--dry-run : Show what would be fixed without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix broken WooCommerce subscriptions by scheduling missing payment actions';

    protected $scheduler;

    /**
     * Create a new command instance.
     */
    public function __construct(SubscriptionScheduler $scheduler)
    {
        parent::__construct();
        $this->scheduler = $scheduler;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $specificId = $this->option('subscription-id');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
        }

        if ($specificId) {
            $this->fixSpecificSubscription($specificId, $dryRun);
        } else {
            $this->fixAllBrokenSubscriptions($dryRun);
        }

        return 0;
    }

    /**
     * Fix a specific subscription
     */
    private function fixSpecificSubscription(int $subscriptionId, bool $dryRun): void
    {
        $this->info("🔧 Fixing subscription ID: {$subscriptionId}");

        // Get subscription details
        $subscription = $this->getSubscriptionDetails($subscriptionId);

        if (!$subscription) {
            $this->error("❌ Subscription {$subscriptionId} not found");
            return;
        }

        $this->displaySubscriptionInfo($subscription);

        // Check if it has a scheduled action
        $hasAction = $this->checkForScheduledAction($subscriptionId);

        if ($hasAction) {
            $this->info("✅ Subscription already has a scheduled action");
            return;
        }

        // Calculate next payment date
        $nextPaymentDate = $this->calculateNextPaymentDate($subscription);

        if (!$nextPaymentDate) {
            $this->error("❌ Could not calculate next payment date");
            return;
        }

        $this->info("📅 Next payment should be: {$nextPaymentDate}");

        if ($dryRun) {
            $this->info("🔍 Would schedule payment action for: {$nextPaymentDate}");
        } else {
            $success = $this->scheduler->scheduleSubscriptionPayment($subscriptionId, $nextPaymentDate);

            if ($success) {
                $this->info("✅ Successfully scheduled payment action");
            } else {
                $this->error("❌ Failed to schedule payment action");
            }
        }
    }

    /**
     * Fix all broken subscriptions
     */
    private function fixAllBrokenSubscriptions(bool $dryRun): void
    {
        $this->info('🔍 Finding all broken subscriptions...');

        // Get all active subscriptions
        $activeSubscriptions = DB::connection('wordpress')
            ->select('SELECT ID, post_date FROM D6sPMX_posts WHERE post_type = "shop_subscription" AND post_status = "wc-active"');

        $this->info("Found " . count($activeSubscriptions) . " active subscriptions");

        // Get subscriptions with scheduled actions
        $withActions = DB::connection('wordpress')
            ->select('SELECT DISTINCT CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(args, \'"subscription_id":\', -1), \'}\', 1) AS UNSIGNED) as sub_id FROM D6sPMX_actionscheduler_actions WHERE hook = "woocommerce_scheduled_subscription_payment" AND status = "pending"');

        $scheduledIds = array_column($withActions, 'sub_id');

        // Find broken subscriptions
        $brokenSubscriptions = [];
        foreach ($activeSubscriptions as $sub) {
            if (!in_array($sub->ID, $scheduledIds)) {
                $brokenSubscriptions[] = $sub->ID;
            }
        }

        $this->info("Found " . count($brokenSubscriptions) . " broken subscriptions");

        if (empty($brokenSubscriptions)) {
            $this->info("🎉 No broken subscriptions found!");
            return;
        }

        // Ask for confirmation unless dry run
        if (!$dryRun && !$this->confirm("Fix " . count($brokenSubscriptions) . " broken subscriptions?")) {
            $this->info("Operation cancelled");
            return;
        }

        $fixed = 0;
        $failed = 0;

        $this->info("🔧 Fixing subscriptions...");

        foreach ($brokenSubscriptions as $subscriptionId) {
            $this->info("Processing subscription {$subscriptionId}...");

            $subscription = $this->getSubscriptionDetails($subscriptionId);
            if (!$subscription) {
                $this->error("  ❌ Subscription not found");
                $failed++;
                continue;
            }

            $nextPaymentDate = $this->calculateNextPaymentDate($subscription);
            if (!$nextPaymentDate) {
                $this->error("  ❌ Could not calculate next payment date");
                $failed++;
                continue;
            }

            if ($dryRun) {
                $this->info("  🔍 Would schedule: {$nextPaymentDate}");
                $fixed++;
            } else {
                $success = $this->scheduler->scheduleSubscriptionPayment($subscriptionId, $nextPaymentDate);
                if ($success) {
                    $this->info("  ✅ Fixed");
                    $fixed++;
                } else {
                    $this->error("  ❌ Failed");
                    $failed++;
                }
            }
        }

        $this->info("📊 Results:");
        $this->info("  Fixed: {$fixed}");
        $this->info("  Failed: {$failed}");

        if (!$dryRun && $fixed > 0) {
            Log::info("Fixed broken subscriptions", [
                'total_broken' => count($brokenSubscriptions),
                'fixed' => $fixed,
                'failed' => $failed
            ]);
        }
    }

    /**
     * Get subscription details from WordPress database
     */
    private function getSubscriptionDetails(int $subscriptionId): ?array
    {
        $sub = DB::connection('wordpress')
            ->select('SELECT post_date FROM D6sPMX_posts WHERE ID = ? AND post_type = "shop_subscription"', [$subscriptionId]);

        if (empty($sub)) {
            return null;
        }

        // Get meta data
        $meta = DB::connection('wordpress')
            ->select('SELECT meta_key, meta_value FROM D6sPMX_postmeta WHERE post_id = ?', [$subscriptionId]);

        $details = [
            'id' => $subscriptionId,
            'created' => $sub[0]->post_date,
            'billing_period' => 'month',
            'billing_interval' => 1,
            'amount' => 0,
            'last_payment_date' => null
        ];

        foreach ($meta as $item) {
            switch ($item->meta_key) {
                case '_billing_period':
                    $details['billing_period'] = $item->meta_value;
                    break;
                case '_billing_interval':
                    $details['billing_interval'] = (int) $item->meta_value;
                    break;
                case '_order_total':
                    $details['amount'] = (float) $item->meta_value;
                    break;
                case '_last_order_date_created':
                    $details['last_payment_date'] = $item->meta_value;
                    break;
            }
        }

        return $details;
    }

    /**
     * Check if subscription has a scheduled action
     */
    private function checkForScheduledAction(int $subscriptionId): bool
    {
        $actions = DB::connection('wordpress')
            ->select('SELECT * FROM D6sPMX_actionscheduler_actions WHERE hook = "woocommerce_scheduled_subscription_payment" AND status = "pending"');

        foreach ($actions as $action) {
            $args = json_decode($action->args, true);
            if (isset($args['subscription_id']) && $args['subscription_id'] == $subscriptionId) {
                return true;
            }
        }

        return false;
    }

    /**
     * Calculate next payment date based on subscription details
     */
    private function calculateNextPaymentDate(array $subscription): ?string
    {
        $lastPayment = $subscription['last_payment_date'] ?? $subscription['created'];

        if (!$lastPayment) {
            return null;
        }

        $lastPaymentTime = strtotime($lastPayment);
        $interval = $subscription['billing_interval'];

        switch ($subscription['billing_period']) {
            case 'week':
                $nextPaymentTime = strtotime("+{$interval} weeks", $lastPaymentTime);
                break;
            case 'month':
                $nextPaymentTime = strtotime("+{$interval} months", $lastPaymentTime);
                break;
            case 'year':
                $nextPaymentTime = strtotime("+{$interval} years", $lastPaymentTime);
                break;
            default:
                $nextPaymentTime = strtotime("+1 month", $lastPaymentTime);
        }

        // If next payment is in the past, schedule for next interval
        $now = time();
        while ($nextPaymentTime <= $now) {
            switch ($subscription['billing_period']) {
                case 'week':
                    $nextPaymentTime = strtotime("+{$interval} weeks", $nextPaymentTime);
                    break;
                case 'month':
                    $nextPaymentTime = strtotime("+{$interval} months", $nextPaymentTime);
                    break;
                case 'year':
                    $nextPaymentTime = strtotime("+{$interval} years", $nextPaymentTime);
                    break;
                default:
                    $nextPaymentTime = strtotime("+1 month", $nextPaymentTime);
            }
        }

        return date('Y-m-d H:i:s', $nextPaymentTime);
    }

    /**
     * Display subscription information
     */
    private function displaySubscriptionInfo(array $subscription): void
    {
        $this->table(
            ['Field', 'Value'],
            [
                ['ID', $subscription['id']],
                ['Created', $subscription['created']],
                ['Amount', '£' . number_format($subscription['amount'], 2)],
                ['Billing', $subscription['billing_interval'] . ' ' . $subscription['billing_period'] . '(s)'],
                ['Last Payment', $subscription['last_payment_date'] ?? 'Never']
            ]
        );
    }
}
