<?php

namespace App\Console\Commands;

use App\Models\VegboxSubscription;
use App\Services\VegboxPaymentService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CalculateChristmasPause extends Command
{
    protected $signature = 'subscriptions:christmas-pause 
                            {--close-date=2025-12-21 : Date farm closes}
                            {--reopen-date=2026-05-01 : Date farm reopens}
                            {--dry-run : Show calculations without making changes}';

    protected $description = 'Calculate pro-rated charges and pause dates for Christmas closure';

    protected VegboxPaymentService $paymentService;

    public function __construct(VegboxPaymentService $paymentService)
    {
        parent::__construct();
        $this->paymentService = $paymentService;
    }

    public function handle()
    {
        $closeDate = Carbon::parse($this->option('close-date'));
        $reopenDate = Carbon::parse($this->option('reopen-date'));
        $dryRun = $this->option('dry-run');
        
        $this->info("Christmas Closure Planning");
        $this->info("Close Date: {$closeDate->format('D, d M Y')}");
        $this->info("Reopen Date: {$reopenDate->format('D, d M Y')}");
        $this->info("Weeks Closed: " . $closeDate->diffInWeeks($reopenDate));
        $this->line("");
        
        // Get all active subscriptions (skip_auto_renewal = false)
        // Exclude test subscriptions (IDs 1, 999, 555, 777)
        $subscriptions = VegboxSubscription::where('skip_auto_renewal', false)
            ->whereNotNull('next_billing_at')
            ->whereNotIn('id', [1, 999, 555, 777]) // Exclude known test subscription IDs
            ->orderBy('next_billing_at')
            ->get();
        
        $this->info("Found {$subscriptions->count()} active subscriptions (excluding test subscriptions)");
        $this->line("");
        
        $actions = [];
        
        foreach ($subscriptions as $sub) {
            $result = $this->calculateSubscriptionPause($sub, $closeDate, $reopenDate);
            $actions[] = $result;
            
            // Display result
            $this->displaySubscriptionPlan($sub, $result);
        }
        
        // Summary
        $this->line("");
        $this->info("=== SUMMARY ===");
        $totalRefunds = collect($actions)->sum('refund_amount');
        $this->info("Total refunds needed: £" . number_format($totalRefunds, 2));
        $this->info("Subscriptions to pause: " . collect($actions)->where('action', 'pause')->count());
        $this->info("Subscriptions to pro-rate: " . collect($actions)->where('refund_amount', '>', 0)->count());
        
        if ($dryRun) {
            $this->warn("\n🔸 DRY RUN MODE - No changes made");
        } else {
            if ($this->confirm('Apply these changes?')) {
                $this->applyChanges($actions);
                $this->info("✅ Changes applied successfully!");
            }
        }
        
        return 0;
    }
    
    protected function calculateSubscriptionPause($sub, $closeDate, $reopenDate)
    {
        $nextBilling = Carbon::parse($sub->next_billing_at);
        $billingPeriod = $sub->billing_period ?? 'month'; // week, month, year
        $billingFreq = (int) ($sub->billing_frequency ?? 1);
        
        // All deliveries are weekly (one per week)
        $deliveriesPerWeek = 1;
        
        // Calculate the billing period - this is what they're PAYING for
        // If next_billing is in future, calculate BACKWARDS from that date
        // If next_billing is in past, they already paid, so calculate FORWARDS from that date
        
        if ($nextBilling->isFuture()) {
            // They haven't been charged yet
            // The period they're being charged FOR starts now and goes until next_billing
            $periodStart = $this->subtractBillingPeriod($nextBilling->copy(), $billingPeriod, $billingFreq);
            $periodEnd = $nextBilling->copy();
        } else {
            // They were already charged on next_billing date
            // So the period started then and goes until the NEXT billing
            $periodStart = $nextBilling->copy();
            $periodEnd = $this->addBillingPeriod($nextBilling->copy(), $billingPeriod, $billingFreq);
        }
        
        // Count weeks between period start and closure
        // For monthly subscriptions, use fixed 4 deliveries per month
        // For weekly subscriptions, count actual weeks
        if ($billingPeriod === 'month') {
            $deliveriesInPeriod = 4 * $billingFreq; // 4 deliveries per month
            
            // Count how many delivery weeks fall before closure
            $deliveriesBeforeClose = 0;
            $currentDate = $periodStart->copy();
            while ($currentDate->isBefore($periodEnd) && $deliveriesBeforeClose < $deliveriesInPeriod) {
                if ($currentDate->isBefore($closeDate)) {
                    $deliveriesBeforeClose++;
                }
                $currentDate->addWeek();
            }
        } else {
            // For weekly/yearly, calculate based on actual weeks
            $weeksInPeriod = ceil($periodStart->diffInWeeks($periodEnd));
            $weeksBeforeClose = min(
                ceil($periodStart->diffInWeeks($closeDate)),
                $weeksInPeriod
            );
            $deliveriesInPeriod = (int) $weeksInPeriod;
            $deliveriesBeforeClose = max(0, (int) $weeksBeforeClose);
        }
        
        // Calculate pro-rated amount
        $fullAmount = (float) $sub->price;
        $pricePerDelivery = $deliveriesInPeriod > 0 ? $fullAmount / $deliveriesInPeriod : 0;
        $amountForDelivered = $deliveriesBeforeClose * $pricePerDelivery;
        $refundAmount = max(0, $fullAmount - $amountForDelivered);
        
        return [
            'subscription_id' => $sub->id,
            'customer' => $sub->customer_name ?? $sub->getWordPressCustomer()->user_login ?? 'Unknown',
            'next_billing' => $nextBilling,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'deliveries_in_period' => $deliveriesInPeriod,
            'deliveries_before_close' => $deliveriesBeforeClose,
            'full_amount' => $fullAmount,
            'amount_for_delivered' => $amountForDelivered,
            'prorated_amount' => $amountForDelivered, // Amount to charge for December
            'refund_amount' => $refundAmount,
            'original_price' => $fullAmount, // Save original price to restore later
            'pause_date' => $closeDate,
            'resume_date' => $reopenDate,
            'action' => $deliveriesBeforeClose < $deliveriesInPeriod ? 'prorate_and_pause' : 'pause',
            'billing_in_future' => $nextBilling->isFuture(), // Whether we can adjust price before billing
        ];
    }
    
    protected function calculateDeliveriesPerPeriod($period, $frequency, $deliveryFreq)
    {
        // Calculate total weeks in billing period
        $weeksInPeriod = 0;
        switch ($period) {
            case 'week':
                $weeksInPeriod = 1 * $frequency;
                break;
            case 'month':
                // Use 52 weeks / 12 months = 4.33 weeks per month for accuracy
                $weeksInPeriod = 4.33 * $frequency;
                break;
            case 'year':
                // Fix: Use 52 weeks per year (not 48)
                // Account for leap years - 52.14 weeks average
                $weeksInPeriod = 52.14 * $frequency;
                break;
        }
        
        // Calculate deliveries based on frequency
        $deliveriesPerWeek = ($deliveryFreq === 'fortnightly') ? 0.5 : 1;
        
        return (int) round($weeksInPeriod * $deliveriesPerWeek);
    }
    
    protected function countDeliveriesBeforeDate($startDate, $endDate, $deliveryFreq)
    {
        $weeksAvailable = $startDate->diffInWeeks($endDate);
        $deliveriesPerWeek = ($deliveryFreq === 'fortnightly') ? 0.5 : 1;
        
        return (int) floor($weeksAvailable * $deliveriesPerWeek);
    }
    
    protected function addBillingPeriod($date, $period, $frequency)
    {
        switch ($period) {
            case 'week':
                return $date->addWeeks($frequency);
            case 'month':
                return $date->addMonths($frequency);
            case 'year':
                return $date->addYears($frequency);
            default:
                return $date->addMonth();
        }
    }
    
    protected function subtractBillingPeriod($date, $period, $frequency)
    {
        switch ($period) {
            case 'week':
                return $date->subWeeks($frequency);
            case 'month':
                return $date->subMonths($frequency);
            case 'year':
                return $date->subYears($frequency);
            default:
                return $date->subMonth();
        }
    }
    
    protected function displaySubscriptionPlan($sub, $result)
    {
        $this->line("─────────────────────────────────────────────");
        $this->info("Subscription #{$sub->id} - {$result['customer']}");
        $this->line("Next Billing: {$result['next_billing']->format('D, d M Y')}");
        $this->line("Period: {$result['period_start']->format('d M')} → {$result['period_end']->format('d M Y')}");
        $this->line("Deliveries: {$result['deliveries_before_close']} / {$result['deliveries_in_period']} before closure");
        $this->line("Full Amount: £{$result['full_amount']}");
        
        if ($result['refund_amount'] > 0) {
            if ($result['billing_in_future']) {
                // Can adjust price before billing
                $this->comment("💳 Will charge pro-rated: £" . number_format($result['prorated_amount'], 2));
                $this->comment("   (Charging only for {$result['deliveries_before_close']} deliveries)");
            } else {
                // Already billed, need refund
                $this->warn("💰 Refund Needed: £" . number_format($result['refund_amount'], 2));
                $this->warn("   (Already charged, must refund for missed deliveries)");
            }
        } else {
            $this->line("✓ No adjustment needed (full period delivered)");
        }
        
        $this->line("📅 Pause on: {$result['pause_date']->format('D, d M Y')}");
        $this->line("🔄 Resume on: {$result['resume_date']->format('D, d M Y')}");
        $this->line("");
    }
    
    protected function applyChanges($actions)
    {
        $proratedCount = 0;
        $retryCount = 0;
        $pauseCount = 0;
        
        foreach ($actions as $action) {
            $sub = VegboxSubscription::find($action['subscription_id']);
            
            if ($action['refund_amount'] > 0) {
                if ($action['billing_in_future']) {
                    // Adjust price to pro-rated amount before next billing
                    $sub->price = number_format($action['prorated_amount'], 2, '.', '');
                    $sub->save();
                    $this->info("✓ Adjusted subscription #{$action['subscription_id']} to £{$sub->price} (was £{$action['original_price']})");
                    $proratedCount++;
                } else {
                    // Already billed but payment failed - update price and retry
                    $originalPrice = $sub->price;
                    $sub->price = number_format($action['prorated_amount'], 2, '.', '');
                    $sub->save();
                    
                    $this->info("Retrying failed payment for subscription #{$action['subscription_id']}...");
                    $this->info("  Charging pro-rated amount: £{$sub->price} (was £{$originalPrice})");
                    
                    // Process payment directly
                    try {
                        $paymentResult = $this->paymentService->processSubscriptionRenewal($sub);
                        
                        if ($paymentResult['success']) {
                            $this->info("  ✓ Payment successful: {$paymentResult['transaction_id']}");
                        } else {
                            $this->error("  ✗ Payment failed: {$paymentResult['error']}");
                            // Restore original price if payment failed
                            $sub->price = $originalPrice;
                            $sub->save();
                        }
                    } catch (\Exception $e) {
                        $this->error("  ✗ Payment error: {$e->getMessage()}");
                        // Restore original price on error
                        $sub->price = $originalPrice;
                        $sub->save();
                    }
                    
                    $retryCount++;
                }
                
                // Pause after payment
                $sub->skip_auto_renewal = true;
                $sub->save();
            } else {
                // No pro-rating needed, just pause
                $sub->skip_auto_renewal = true;
                $sub->save();
                $this->info("✓ Paused subscription #{$action['subscription_id']}");
                $pauseCount++;
            }
        }
        
        $this->line("");
        $this->info("Summary:");
        $this->info("- {$proratedCount} subscriptions adjusted to pro-rated amounts");
        $this->info("- {$retryCount} failed payments retried with pro-rated amounts");
        $this->info("- {$pauseCount} subscriptions paused without adjustment");
        $this->info("- All subscriptions now paused for winter closure");
        $this->line("");
        $this->comment("⚠️  Remember to restore original prices when resuming in May!");
    }
}
