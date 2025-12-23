<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\VegboxSubscription;
use App\Notifications\SubscriptionRenewed;
use App\Notifications\SubscriptionPaymentFailed;
use App\Notifications\LowBalanceWarning;
use App\Notifications\SubscriptionCancelled;
use App\Notifications\DailyRenewalSummary;
use Illuminate\Console\Command;

class TestNotifications extends Command
{
    protected $signature = 'vegbox:test-notifications {--type=all : Notification type to test (all, renewed, failed, warning, cancelled, summary)}';
    protected $description = 'Test vegbox subscription notifications';

    public function handle()
    {
        $type = $this->option('type');

        // Get a test user (your account)
        $user = User::where('email', 'middleworldfarms@gmail.com')->first();

        if (!$user) {
            $this->error('Test user not found');
            return Command::FAILURE;
        }

        // Get a test subscription
        $subscription = VegboxSubscription::where('subscriber_id', $user->id)->first();

        if (!$subscription) {
            $this->error('No subscription found for test user');
            return Command::FAILURE;
        }

        $this->info("Testing notifications for: {$user->email}");
        $this->info("Using subscription ID: {$subscription->id}");
        $this->newLine();

        if ($type === 'all' || $type === 'renewed') {
            $this->info('📧 Sending SubscriptionRenewed notification...');
            $user->notify(new SubscriptionRenewed($subscription, [
                'amount' => 25.00,
                'transaction_id' => 'test-' . time(),
                'new_balance' => 635.00
            ]));
            $this->line('✅ SubscriptionRenewed sent');
            $this->newLine();
        }

        if ($type === 'all' || $type === 'failed') {
            $this->info('📧 Sending SubscriptionPaymentFailed notification...');
            $user->notify(new SubscriptionPaymentFailed(
                $subscription,
                'Insufficient funds for renewal',
                10.50
            ));
            $this->line('✅ SubscriptionPaymentFailed sent');
            $this->newLine();
        }

        if ($type === 'all' || $type === 'warning') {
            $this->info('📧 Sending LowBalanceWarning notification...');
            $user->notify(new LowBalanceWarning($subscription, 15.00, 25.00));
            $this->line('✅ LowBalanceWarning sent');
            $this->newLine();
        }

        if ($type === 'all' || $type === 'cancelled') {
            $this->info('📧 Sending SubscriptionCancelled notification...');
            $user->notify(new SubscriptionCancelled($subscription, 'Test cancellation', false));
            $this->line('✅ SubscriptionCancelled sent');
            $this->newLine();
        }

        if ($type === 'all' || $type === 'summary') {
            $this->info('📧 Sending DailyRenewalSummary notification...');
            $user->notify(new DailyRenewalSummary([
                'total_processed' => 5,
                'successful' => 4,
                'failed' => 1,
                'total_revenue' => 100.00,
                'failed_subscriptions' => [
                    ['id' => 123, 'reason' => 'Insufficient funds']
                ]
            ]));
            $this->line('✅ DailyRenewalSummary sent');
            $this->newLine();
        }

        $this->info('All notifications queued successfully!');
        $this->info('Check your email or run: php artisan queue:work to process them');
        $this->info('Or check database notifications: SELECT * FROM notifications WHERE notifiable_id = ' . $user->id);

        return Command::SUCCESS;
    }
}
