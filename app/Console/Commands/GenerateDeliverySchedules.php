<?php

namespace App\Console\Commands;

use App\Models\VegboxSubscription;
use App\Models\DeliverySchedule;
use Illuminate\Console\Command;
use Carbon\Carbon;

class GenerateDeliverySchedules extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vegbox:generate-deliveries {--days=14 : Number of days ahead to generate schedules for} {--dry-run : Show what would be created without actually creating}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate delivery schedules for active vegbox subscriptions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $daysAhead = (int) $this->option('days');
        $isDryRun = $this->option('dry-run');

        $this->info("Generating delivery schedules for the next {$daysAhead} days...");

        // Get all active subscriptions that need delivery schedules
        $subscriptions = VegboxSubscription::query()
            ->active()
            ->whereNotNull('delivery_day')
            ->whereNotNull('next_delivery_date')
            ->get();

        $this->info("Found {$subscriptions->count()} active subscriptions with delivery schedules.");

        $createdCount = 0;
        $skippedCount = 0;

        foreach ($subscriptions as $subscription) {
            $schedulesCreated = $this->generateSchedulesForSubscription($subscription, $daysAhead, $isDryRun);
            $createdCount += $schedulesCreated['created'];
            $skippedCount += $schedulesCreated['skipped'];
        }

        if ($isDryRun) {
            $this->info("DRY RUN: Would create {$createdCount} delivery schedules, skipped {$skippedCount} existing ones.");
        } else {
            $this->info("Created {$createdCount} delivery schedules, skipped {$skippedCount} existing ones.");
        }

        return Command::SUCCESS;
    }

    /**
     * Generate delivery schedules for a specific subscription.
     */
    private function generateSchedulesForSubscription(VegboxSubscription $subscription, int $daysAhead, bool $isDryRun): array
    {
        $created = 0;
        $skipped = 0;

        $startDate = today();
        $endDate = today()->addDays($daysAhead);

        // Get the delivery dates for this subscription within the range
        $deliveryDates = $this->getDeliveryDatesForSubscription($subscription, $startDate, $endDate);

        foreach ($deliveryDates as $deliveryDate) {
            // Check if a schedule already exists for this date
            $existingSchedule = DeliverySchedule::where('vegbox_subscription_id', $subscription->id)
                ->whereDate('scheduled_date', $deliveryDate)
                ->first();

            if ($existingSchedule) {
                $skipped++;
                continue;
            }

            if (!$isDryRun) {
                DeliverySchedule::create([
                    'vegbox_subscription_id' => $subscription->id,
                    'scheduled_date' => $deliveryDate,
                    'delivery_status' => 'pending',
                    'delivery_address' => $this->getDeliveryAddress($subscription),
                    'special_instructions' => $subscription->special_instructions,
                    'box_contents' => $this->generateBoxContents($subscription),
                ]);
            }

            $created++;
        }

        return ['created' => $created, 'skipped' => $skipped];
    }

    /**
     * Get delivery dates for a subscription within a date range.
     */
    private function getDeliveryDatesForSubscription(VegboxSubscription $subscription, Carbon $startDate, Carbon $endDate): array
    {
        $deliveryDates = [];
        $currentDate = $startDate->copy();

        // Find the first delivery date on or after startDate
        while ($currentDate->dayOfWeek !== $this->getDayIndex($subscription->delivery_day)) {
            $currentDate->addDay();
            if ($currentDate->greaterThan($endDate)) {
                return $deliveryDates;
            }
        }

        // Generate delivery dates based on frequency
        $frequency = $subscription->plan->delivery_frequency ?? 'weekly';
        $interval = $frequency === 'bi-weekly' ? 14 : 7;

        while ($currentDate->lessThanOrEqualTo($endDate)) {
            // Skip if subscription is paused during this period
            if ($subscription->pause_until && $currentDate->lessThanOrEqualTo($subscription->pause_until)) {
                $currentDate->addDays($interval);
                continue;
            }

            $deliveryDates[] = $currentDate->copy();
            $currentDate->addDays($interval);
        }

        return $deliveryDates;
    }

    /**
     * Get the day index for Carbon (0 = Sunday, 1 = Monday, etc.)
     */
    private function getDayIndex(string $day): int
    {
        return match(strtolower($day)) {
            'sunday' => 0,
            'monday' => 1,
            'tuesday' => 2,
            'wednesday' => 3,
            'thursday' => 4,
            'friday' => 5,
            'saturday' => 6,
            default => 1, // Default to Monday
        };
    }

    /**
     * Get the delivery address for a subscription.
     */
    private function getDeliveryAddress(VegboxSubscription $subscription): string
    {
        // For now, return a placeholder. In a real implementation,
        // this would look up the user's delivery address
        return 'Delivery address to be implemented';
    }

    /**
     * Generate box contents based on the subscription plan.
     */
    private function generateBoxContents(VegboxSubscription $subscription): array
    {
        // This would contain logic to determine what's in each box
        // For now, return a basic structure
        return [
            'box_size' => $subscription->plan->box_size,
            'estimated_items' => $this->getEstimatedItemCount($subscription->plan),
            'seasonal_notes' => 'Contents vary based on seasonal availability',
            'generated_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * Get estimated item count based on box size.
     */
    private function getEstimatedItemCount($plan): int
    {
        return match($plan->box_size) {
            'small' => 6,
            'medium' => 10,
            'large' => 14,
            default => 8,
        };
    }
}
