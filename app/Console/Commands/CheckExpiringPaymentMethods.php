<?php

namespace App\Console\Commands;

use App\Models\UserPaymentMethod;
use App\Notifications\PaymentMethodExpiring;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CheckExpiringPaymentMethods extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payment-methods:check-expiring 
                            {--days=30 : Days before expiry to send notification}
                            {--dry-run : Show what would be notified without sending}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for expiring payment methods and notify users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $daysAhead = (int) $this->option('days');
        $isDryRun = $this->option('dry-run');
        
        $this->info("Checking for payment methods expiring within {$daysAhead} days" . ($isDryRun ? ' (DRY RUN)' : ''));
        $this->newLine();

        $currentMonth = now()->month;
        $currentYear = now()->year;
        
        // Calculate expiry cutoff (e.g., 30 days from now)
        $cutoffDate = now()->addDays($daysAhead);
        $cutoffMonth = $cutoffDate->month;
        $cutoffYear = $cutoffDate->year;

        // Find payment methods expiring soon
        $expiringMethods = UserPaymentMethod::query()
            ->where('is_default', true) // Only notify for default payment methods
            ->where(function ($query) use ($currentMonth, $currentYear, $cutoffMonth, $cutoffYear) {
                // Expiring this month or within cutoff period
                $query->where(function ($q) use ($currentYear, $cutoffYear, $cutoffMonth) {
                    $q->where('card_exp_year', $currentYear)
                      ->where('card_exp_month', '>=', now()->month);
                })->orWhere(function ($q) use ($cutoffYear, $cutoffMonth) {
                    $q->where('card_exp_year', $cutoffYear)
                      ->where('card_exp_month', '<=', $cutoffMonth);
                });
            })
            ->with('user')
            ->get();

        if ($expiringMethods->isEmpty()) {
            $this->info('No expiring payment methods found.');
            return Command::SUCCESS;
        }

        $this->info("Found {$expiringMethods->count()} expiring payment methods");
        $this->newLine();

        $notified = 0;

        foreach ($expiringMethods as $method) {
            $expiryDate = Carbon::create(
                $method->card_exp_year,
                $method->card_exp_month,
                1
            )->endOfMonth();

            $daysUntilExpiry = now()->diffInDays($expiryDate, false);

            $this->line(sprintf(
                "User: %s | Card: %s •••• %s | Expires: %02d/%04d | Days: %d",
                $method->user->email,
                ucfirst($method->card_brand),
                $method->card_last4,
                $method->card_exp_month,
                $method->card_exp_year,
                $daysUntilExpiry
            ));

            if (!$isDryRun) {
                try {
                    $method->user->notify(new PaymentMethodExpiring($method));
                    $this->info("  ✓ Notification sent");
                    $notified++;
                    
                    Log::info('Payment method expiry notification sent', [
                        'user_id' => $method->user_id,
                        'payment_method_id' => $method->id,
                        'expires' => $expiryDate->format('Y-m-d'),
                    ]);
                } catch (\Exception $e) {
                    $this->error("  ✗ Failed to send notification: {$e->getMessage()}");
                    
                    Log::error('Failed to send payment expiry notification', [
                        'user_id' => $method->user_id,
                        'payment_method_id' => $method->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        $this->newLine();
        $this->info('=== SUMMARY ===');
        $this->info("Expiring methods: {$expiringMethods->count()}");
        
        if (!$isDryRun) {
            $this->info("Notifications sent: {$notified}");
        } else {
            $this->warn('[DRY RUN] No notifications sent');
        }

        return Command::SUCCESS;
    }
}
