<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\HarvestLog;
use App\Models\StockItem;
use App\Models\CropPlan;
use Illuminate\Support\Facades\DB;

class ClearFarmOSTestDataSeeder extends Seeder
{
    /**
     * Remove ALL test data created by FarmOSTestDataSeeder.
     * 
     * This seeder safely removes only test data that has "TEST" prefixes
     * and will NOT touch any real production data.
     */
    public function run()
    {
        $this->command->info('🧹 Clearing FarmOS test data...');
        
        // Count test data before deletion
        $harvestCount = HarvestLog::where('crop_name', 'LIKE', 'TEST -%')
            ->orWhere('farmos_id', 'LIKE', 'TEST-%')
            ->count();
            
        $stockCount = StockItem::where('name', 'LIKE', 'TEST -%')
            ->count();
            
        $planCount = CropPlan::where('crop_name', 'LIKE', 'TEST -%')
            ->count();

        $this->command->info("Found {$harvestCount} test harvest logs to delete");
        $this->command->info("Found {$stockCount} test stock items to delete");
        $this->command->info("Found {$planCount} test crop plans to delete");

        if ($harvestCount + $stockCount + $planCount === 0) {
            $this->command->info('✅ No test data found to clear!');
            return;
        }

        // Confirm deletion
        if ($this->command->confirm('Are you sure you want to delete all FarmOS test data?')) {
            
            // Delete test harvest logs
            $deleted = HarvestLog::where('crop_name', 'LIKE', 'TEST -%')
                ->orWhere('farmos_id', 'LIKE', 'TEST-%')
                ->delete();
            $this->command->info("Deleted {$deleted} test harvest logs");

            // Delete test stock items
            $deleted = StockItem::where('name', 'LIKE', 'TEST -%')
                ->delete();
            $this->command->info("Deleted {$deleted} test stock items");

            // Delete test crop plans
            $deleted = CropPlan::where('crop_name', 'LIKE', 'TEST -%')
                ->delete();
            $this->command->info("Deleted {$deleted} test crop plans");

            $this->command->info('✅ All FarmOS test data has been cleared!');
        } else {
            $this->command->info('❌ Test data cleanup cancelled.');
        }
    }
}
