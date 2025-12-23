<?php

namespace App\Console\Commands;

use App\Models\DeliverySchedule;
use Illuminate\Console\Command;
use Carbon\Carbon;

class ProcessDeliveries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vegbox:process-deliveries {--date= : Process deliveries for specific date (YYYY-MM-DD)} {--dry-run : Show what would be processed without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process delivery schedules and update statuses';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $targetDate = $this->option('date') ? Carbon::parse($this->option('date')) : today();
        $isDryRun = $this->option('dry-run');

        $this->info("Processing deliveries for {$targetDate->format('Y-m-d')}...");

        // Get deliveries scheduled for today that are still pending
        $deliveries = DeliverySchedule::whereDate('scheduled_date', $targetDate)
            ->where('delivery_status', 'pending')
            ->with('vegboxSubscription')
            ->get();

        $this->info("Found {$deliveries->count()} pending deliveries for {$targetDate->format('Y-m-d')}.");

        $processedCount = 0;
        $failedCount = 0;

        foreach ($deliveries as $delivery) {
            try {
                $this->processDelivery($delivery, $isDryRun);
                $processedCount++;
            } catch (\Exception $e) {
                $this->error("Failed to process delivery {$delivery->id}: {$e->getMessage()}");
                $failedCount++;
            }
        }

        if ($isDryRun) {
            $this->info("DRY RUN: Would process {$processedCount} deliveries, {$failedCount} failed.");
        } else {
            $this->info("Processed {$processedCount} deliveries, {$failedCount} failed.");
        }

        // Check for overdue deliveries
        $this->checkOverdueDeliveries($targetDate);

        return Command::SUCCESS;
    }

    /**
     * Process a single delivery.
     */
    private function processDelivery(DeliverySchedule $delivery, bool $isDryRun): void
    {
        // In a real implementation, this would integrate with delivery tracking systems
        // For now, we'll simulate delivery processing

        if ($isDryRun) {
            $this->line("Would mark delivery {$delivery->id} as delivered for subscription {$delivery->vegbox_subscription_id}");
            return;
        }

        // Mark delivery as completed
        $delivery->markAsDelivered(
            deliveredBy: 'Automated System',
            notes: 'Auto-processed delivery'
        );

        $this->line("Marked delivery {$delivery->id} as delivered");
    }

    /**
     * Check for overdue deliveries and alert if necessary.
     */
    private function checkOverdueDeliveries(Carbon $targetDate): void
    {
        $overdueDeliveries = DeliverySchedule::where('scheduled_date', '<', $targetDate)
            ->where('delivery_status', 'pending')
            ->with('vegboxSubscription')
            ->get();

        if ($overdueDeliveries->isEmpty()) {
            return;
        }

        $this->warn("Found {$overdueDeliveries->count()} overdue deliveries:");

        foreach ($overdueDeliveries as $delivery) {
            $daysOverdue = $targetDate->diffInDays($delivery->scheduled_date);
            $this->warn("- Delivery {$delivery->id} for subscription {$delivery->vegbox_subscription_id} is {$daysOverdue} days overdue");

            // In a real implementation, this would send alerts to delivery team
            // For now, we'll just log it
        }

        // Could send summary email to delivery manager
        $this->info("Overdue deliveries summary logged. Consider sending alerts to delivery team.");
    }
}
