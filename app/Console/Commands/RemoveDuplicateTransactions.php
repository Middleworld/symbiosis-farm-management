<?php

namespace App\Console\Commands;

use App\Models\BankTransaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RemoveDuplicateTransactions extends Command
{
    protected $signature = 'bank:remove-duplicates {--dry-run : Show what would be deleted without actually deleting}';
    protected $description = 'Find and remove duplicate bank transactions';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No transactions will be deleted');
        }
        
        $this->info('Searching for duplicate transactions...');
        $this->newLine();
        
        // Find transactions with same date, description, amount, and type
        $duplicates = DB::select("
            SELECT DATE(transaction_date) as trans_date, description, amount, type, COUNT(*) as count, GROUP_CONCAT(id ORDER BY id) as ids
            FROM bank_transactions
            GROUP BY DATE(transaction_date), description, amount, type
            HAVING count > 1
            ORDER BY trans_date DESC
        ");
        
        if (empty($duplicates)) {
            $this->info('✓ No duplicate transactions found');
            return 0;
        }
        
        $this->warn('Found ' . count($duplicates) . ' groups of duplicate transactions');
        $this->newLine();
        
        $totalDeleted = 0;
        $totalAmount = 0;
        
        foreach ($duplicates as $dup) {
            $ids = explode(',', $dup->ids);
            $count = (int)$dup->count;
            $toDelete = $count - 1; // Keep one, delete the rest
            
            $this->line(sprintf(
                '%s | %s | £%.2f | %dx duplicates',
                $dup->trans_date,
                $dup->type,
                $dup->amount,
                $count
            ));
            $this->line('  ' . substr($dup->description, 0, 60));
            
            if (!$dryRun) {
                // Keep the first ID, delete the rest
                $idsToDelete = array_slice($ids, 1);
                BankTransaction::whereIn('id', $idsToDelete)->delete();
                
                $this->line('  ✓ Deleted ' . count($idsToDelete) . ' duplicate(s)');
                $totalDeleted += count($idsToDelete);
                
                if ($dup->type === 'debit') {
                    $totalAmount += ($dup->amount * $toDelete);
                }
            } else {
                $this->line('  Would delete ' . $toDelete . ' duplicate(s)');
                $totalDeleted += $toDelete;
                
                if ($dup->type === 'debit') {
                    $totalAmount += ($dup->amount * $toDelete);
                }
            }
            
            $this->newLine();
        }
        
        $this->newLine();
        
        if ($dryRun) {
            $this->warn('DRY RUN SUMMARY:');
            $this->info('  Would delete: ' . $totalDeleted . ' duplicate transactions');
            $this->info('  Would remove: £' . number_format($totalAmount, 2) . ' of duplicate expenses');
            $this->newLine();
            $this->line('Run without --dry-run to actually delete duplicates');
        } else {
            $this->info('SUMMARY:');
            $this->info('  Deleted: ' . $totalDeleted . ' duplicate transactions');
            $this->info('  Removed: £' . number_format($totalAmount, 2) . ' of duplicate expenses');
            
            // Show updated totals
            $this->newLine();
            $totalIncome = BankTransaction::where('type', 'credit')->sum('amount');
            $totalExpenses = BankTransaction::where('type', 'debit')->sum('amount');
            $netBalance = $totalIncome - $totalExpenses;
            
            $this->info('New totals:');
            $this->line('  Income:   £' . number_format($totalIncome, 2));
            $this->line('  Expenses: £' . number_format($totalExpenses, 2));
            $this->line('  Net:      £' . number_format($netBalance, 2));
        }
        
        return 0;
    }
}
