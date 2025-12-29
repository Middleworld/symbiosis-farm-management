<?php

namespace App\Console\Commands;

use App\Models\BankTransaction;
use App\Services\BankTransactionCategorizationService;
use Illuminate\Console\Command;

class RecategorizeBankTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bank:recategorize {--force : Force recategorization of all transactions}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recategorize bank transactions using improved categorization rules';

    protected $categorizationService;

    public function __construct(BankTransactionCategorizationService $categorizationService)
    {
        parent::__construct();
        $this->categorizationService = $categorizationService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $force = $this->option('force');
        
        $this->info('Starting recategorization...');
        
        $query = BankTransaction::query();
        
        // Only recategorize uncategorized or other_expense/other_income if not forcing
        if (!$force) {
            $query->whereIn('category', ['other_expense', 'other_income', 'uncategorized', null]);
        }
        
        $transactions = $query->get();
        $total = $transactions->count();
        
        if ($total === 0) {
            $this->info('No transactions to recategorize.');
            return 0;
        }
        
        $this->info("Found {$total} transactions to process...");
        
        $bar = $this->output->createProgressBar($total);
        $bar->start();
        
        $updated = 0;
        $categoryChanges = [];
        
        foreach ($transactions as $transaction) {
            $oldCategory = $transaction->category;
            $newCategory = $this->categorizationService->categorize($transaction);
            
            if ($oldCategory !== $newCategory) {
                $transaction->category = $newCategory;
                $transaction->save();
                $updated++;
                
                $change = ($oldCategory ?: 'null') . ' → ' . $newCategory;
                if (!isset($categoryChanges[$change])) {
                    $categoryChanges[$change] = 0;
                }
                $categoryChanges[$change]++;
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine(2);
        
        $this->info("✓ Recategorized {$updated} of {$total} transactions");
        
        if (count($categoryChanges) > 0) {
            $this->newLine();
            $this->info('Category changes:');
            foreach ($categoryChanges as $change => $count) {
                $this->line("  • {$change}: {$count} transaction(s)");
            }
        }
        
        $this->newLine();
        $this->info('Category summary:');
        $summary = BankTransaction::select('category', \DB::raw('count(*) as count'))
            ->groupBy('category')
            ->orderBy('count', 'desc')
            ->get();
            
        foreach ($summary as $item) {
            $label = $this->categorizationService->getCategoryLabel($item->category);
            $this->line("  • {$label}: {$item->count} transaction(s)");
        }
        
        return 0;
    }
}
