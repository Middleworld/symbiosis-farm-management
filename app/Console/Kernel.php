<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\ManageSubscription::class,
        Commands\GenerateDeliverySchedules::class,
        Commands\ProcessDeliveries::class,
        Commands\ProcessSubscriptionRenewals::class,
        Commands\SyncWooVegboxPlans::class,
        Commands\SyncWooVegboxSubscriptions::class,
        Commands\ImportWooStripeCustomers::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Generate delivery schedules daily at 2 AM
        $schedule->command('vegbox:generate-deliveries --days=30')
                ->dailyAt('02:00')
                ->withoutOverlapping()
                ->runInBackground();

        // Process deliveries daily at 6 AM (after delivery window)
        $schedule->command('vegbox:process-deliveries')
                ->dailyAt('06:00')
                ->withoutOverlapping()
                ->runInBackground();

        // Process subscription renewals daily at 8 AM
        $schedule->command('vegbox:process-renewals')
                ->dailyAt('08:00')
                ->withoutOverlapping()
                ->runInBackground();

        // Check for overdue deliveries hourly during business hours
        $schedule->command('vegbox:process-deliveries')
                ->hourly()
                ->between('8:00', '18:00')
                ->when(function () {
                    // Only run if there are overdue deliveries
                    return \App\Models\DeliverySchedule::where('scheduled_date', '<', today())
                            ->where('delivery_status', 'pending')
                            ->exists();
                });

        // ===== FarmOS & Stock Sync Tasks =====
        
        // Sync FarmOS harvests to local stock every 15 minutes
        $schedule->command('farmos:sync-harvests-to-stock')
                ->everyFifteenMinutes()
                ->withoutOverlapping()
                ->appendOutputTo(storage_path('logs/farmos-harvest-sync.log'));

        // Sync local stock to WooCommerce every 30 minutes
        $schedule->command('stock:sync-to-woocommerce')
                ->everyThirtyMinutes()
                ->withoutOverlapping()
                ->appendOutputTo(storage_path('logs/woocommerce-stock-sync.log'));

        // Sync FarmOS varieties daily at 3 AM
        $schedule->command('farmos:sync-varieties:legacy')
                ->dailyAt('03:00')
                ->withoutOverlapping()
                ->appendOutputTo(storage_path('logs/farmos-variety-sync.log'));

        // Retry failed vegbox payments daily at 10 AM
        $schedule->command('vegbox:retry-failed-payments')
                ->dailyAt('10:00')
                ->withoutOverlapping()
                ->appendOutputTo(storage_path('logs/vegbox-payment-retry.log'));

        // Check for expiring payment methods weekly on Monday at 9 AM
        $schedule->command('payment-methods:check-expiring --days=30')
                ->weeklyOn(1, '09:00')
                ->appendOutputTo(storage_path('logs/payment-method-expiry-check.log'));

        // Monitor subscription health daily at 7 AM
        $schedule->command('vegbox:monitor-health')
                ->dailyAt('07:00')
                ->appendOutputTo(storage_path('logs/vegbox-health-monitor.log'));
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
