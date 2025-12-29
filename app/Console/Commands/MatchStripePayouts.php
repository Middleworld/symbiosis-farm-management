<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\StripeService;
use App\Models\BankTransaction;
use Carbon\Carbon;

class MatchStripePayouts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stripe:match-payouts {--days=90 : Number of days to look back} {--dry-run : Preview matches without saving}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Match Stripe payouts with bank transactions';

    protected $stripeService;

    public function __construct(StripeService $stripeService)
    {
        parent::__construct();
        $this->stripeService = $stripeService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days');
        $dryRun = $this->option('dry-run');
        
        $this->info("Matching Stripe payouts from last {$days} days...");
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be saved');
        }
        $this->newLine();
        
        // Get Stripe payouts
        $payouts = $this->stripeService->getPayouts(['days' => $days]);
        
        if ($payouts->isEmpty()) {
            $this->warn('No Stripe payouts found in the specified period');
            return Command::SUCCESS;
        }
        
        $this->info("Found {$payouts->count()} Stripe payouts");
        $this->newLine();
        
        $matched = 0;
        $skipped = 0;
        $notFound = 0;
        
        foreach ($payouts as $payout) {
            // Skip if not paid yet
            if ($payout['status'] !== 'paid') {
                continue;
            }
            
            $arrivalDate = $payout['arrival_date'];
            $amount = $payout['amount'];
            
            // Look for matching bank transaction (Stripe deposit)
            // Search +/- 3 days from arrival date
            $bankTx = BankTransaction::where('type', 'credit')
                ->where('description', 'like', '%STRIPE%')
                ->whereBetween('transaction_date', [
                    $arrivalDate->copy()->subDays(3),
                    $arrivalDate->copy()->addDays(3)
                ])
                ->where('amount', $amount)
                ->whereNull('stripe_payout_id')
                ->first();
            
            if ($bankTx) {
                $this->line("✓ Matched: {$arrivalDate->format('Y-m-d')} | £{$amount} | Payout {$payout['id']}");
                
                if (!$dryRun) {
                    // Get charges for this payout
                    $charges = $this->stripeService->getChargesForPayout($payout['id']);
                    
                    $bankTx->stripe_payout_id = $payout['id'];
                    $bankTx->stripe_charges = $charges->toJson();
                    $bankTx->save();
                    
                    $this->line("  → Linked to bank transaction #{$bankTx->id}");
                    $this->line("  → Included {$charges->count()} charges");
                }
                
                $matched++;
            } else {
                // Check if already matched
                $existing = BankTransaction::where('stripe_payout_id', $payout['id'])->first();
                
                if ($existing) {
                    $this->line("⊘ Skipped: {$arrivalDate->format('Y-m-d')} | £{$amount} | Already matched");
                    $skipped++;
                } else {
                    $this->warn("✗ Not found: {$arrivalDate->format('Y-m-d')} | £{$amount} | Payout {$payout['id']}");
                    $notFound++;
                }
            }
        }
        
        $this->newLine();
        $this->info("Summary:");
        $this->line("  Matched: {$matched}");
        $this->line("  Skipped (already matched): {$skipped}");
        $this->line("  Not found in bank: {$notFound}");
        
        if ($dryRun) {
            $this->newLine();
            $this->warn('DRY RUN - Run without --dry-run to save matches');
        }
        
        return Command::SUCCESS;
    }
}
