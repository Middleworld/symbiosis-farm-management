<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Laravel equivalent of WooCommerce Subscriptions Action Scheduler
 * Replicates the core scheduling logic without GPL license dependencies
 */
class SubscriptionScheduler
{
    /**
     * Schedule a subscription payment
     * Equivalent to WooCommerce Subscriptions' update_date() method
     */
    public function scheduleSubscriptionPayment(int $subscriptionId, string $nextPaymentDate): bool
    {
        try {
            // Convert to timestamp
            $timestamp = strtotime($nextPaymentDate);
            $now = time();

            // Only schedule if it's in the future
            if ($timestamp <= $now) {
                Log::info("Not scheduling subscription payment for {$subscriptionId} - date is in the past", [
                    'next_payment' => $nextPaymentDate,
                    'timestamp' => $timestamp,
                    'now' => $now
                ]);
                return false;
            }

            // Check if action already exists for this subscription
            $existingAction = DB::connection('wordpress')
                ->table('actionscheduler_actions')
                ->where('hook', 'woocommerce_scheduled_subscription_payment')
                ->whereRaw("JSON_EXTRACT(args, '$.subscription_id') = ?", [$subscriptionId])
                ->first();

            if ($existingAction && $existingAction->status !== 'complete') {
                // Update existing pending action if the date is different
                if (strtotime($existingAction->scheduled_date_gmt) !== $timestamp) {
                    DB::connection('wordpress')
                        ->table('actionscheduler_actions')
                        ->where('action_id', $existingAction->action_id)
                        ->update([
                            'scheduled_date_gmt' => date('Y-m-d H:i:s', $timestamp),
                            'scheduled_date_local' => date('Y-m-d H:i:s', $timestamp),
                            'last_attempt_gmt' => null,
                            'last_attempt_local' => null
                        ]);

                    Log::info("Updated existing subscription payment action for {$subscriptionId}", [
                        'new_date' => date('Y-m-d H:i:s', $timestamp)
                    ]);
                }
                return true;
            }

            // Create new action (either no existing action, or existing was complete)
            if ($existingAction && $existingAction->status === 'complete') {
                Log::info("Found completed action for subscription {$subscriptionId}, creating new action", [
                    'existing_action_id' => $existingAction->action_id,
                    'existing_scheduled_date' => $existingAction->scheduled_date_gmt
                ]);
            }

            $actionData = [
                'hook' => 'woocommerce_scheduled_subscription_payment',
                'status' => 'pending',
                'args' => json_encode(['subscription_id' => $subscriptionId]),
                'scheduled_date_gmt' => date('Y-m-d H:i:s', $timestamp),
                'scheduled_date_local' => date('Y-m-d H:i:s', $timestamp),
                'group_id' => 0,
                'last_attempt_gmt' => null,
                'last_attempt_local' => null,
                'claim_id' => 0,
                'extended_args' => 'N;'
            ];

            $inserted = DB::connection('wordpress')
                ->table('actionscheduler_actions')
                ->insert($actionData);

            if ($inserted) {
                Log::info("Created new subscription payment action for {$subscriptionId}", [
                    'scheduled_date' => date('Y-m-d H:i:s', $timestamp)
                ]);
            }

            return true;

        } catch (\Exception $e) {
            Log::error("Failed to schedule subscription payment for {$subscriptionId}", [
                'error' => $e->getMessage(),
                'next_payment' => $nextPaymentDate
            ]);
            return false;
        }
    }

    /**
     * Unschedule a subscription payment
     * Equivalent to WooCommerce Subscriptions' delete_date() method
     */
    public function unscheduleSubscriptionPayment(int $subscriptionId): bool
    {
        try {
            $deleted = DB::connection('wordpress')
                ->table('actionscheduler_actions')
                ->where('hook', 'woocommerce_scheduled_subscription_payment')
                ->where('status', 'pending')
                ->whereRaw("JSON_EXTRACT(args, '$.subscription_id') = ?", [$subscriptionId])
                ->delete();

            if ($deleted > 0) {
                Log::info("Unscheduled subscription payment actions for {$subscriptionId}", [
                    'actions_deleted' => $deleted
                ]);
            }

            return true;

        } catch (\Exception $e) {
            Log::error("Failed to unschedule subscription payment for {$subscriptionId}", [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get all pending subscription payment actions
     */
    public function getPendingSubscriptionPayments(): array
    {
        try {
            $actions = DB::connection('wordpress')
                ->table('actionscheduler_actions')
                ->where('hook', 'woocommerce_scheduled_subscription_payment')
                ->where('status', 'pending')
                ->orderBy('scheduled_date_gmt')
                ->get();

            $result = [];
            foreach ($actions as $action) {
                $args = json_decode($action->args, true);
                if (isset($args['subscription_id'])) {
                    $result[] = [
                        'action_id' => $action->action_id,
                        'subscription_id' => $args['subscription_id'],
                        'scheduled_date' => $action->scheduled_date_gmt,
                        'is_overdue' => strtotime($action->scheduled_date_gmt) < time()
                    ];
                }
            }

            return $result;

        } catch (\Exception $e) {
            Log::error("Failed to get pending subscription payments", [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Process overdue subscription payments
     * This would be called by a cron job
     */
    public function processOverduePayments(): array
    {
        $pendingActions = $this->getPendingSubscriptionPayments();
        $processed = [];
        $failed = [];

        foreach ($pendingActions as $action) {
            if ($action['is_overdue']) {
                // Here we would trigger the payment processing
                // For now, just log it
                Log::warning("Overdue subscription payment detected", [
                    'subscription_id' => $action['subscription_id'],
                    'scheduled_date' => $action['scheduled_date'],
                    'days_overdue' => floor((time() - strtotime($action['scheduled_date'])) / 86400)
                ]);

                $processed[] = $action['subscription_id'];
            }
        }

        return [
            'processed' => $processed,
            'failed' => $failed,
            'total_overdue' => count($processed)
        ];
    }
}
