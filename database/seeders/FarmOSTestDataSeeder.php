<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\HarvestLog;
use App\Models\StockItem;
use App\Models\CropPlan;
use Carbon\Carbon;

class FarmOSTestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * ALL DATA CREATED BY THIS SEEDER IS CLEARLY MARKED AS TEST DATA
     * AND CAN BE SAFELY DELETED USING THE ClearFarmOSTestDataSeeder
     */
    public function run()
    {
        $this->command->info('Creating FarmOS test data with clear TEST prefixes...');
        
        // Create test harvest logs
        $this->createTestHarvestLogs();
        
        // Create test stock items
        $this->createTestStockItems();
        
        // Create test crop plans
        $this->createTestCropPlans();
        
        $this->command->info('✅ FarmOS test data created successfully!');
        $this->command->warn('⚠️  Remember: All test data has "TEST-" prefixes and can be cleared with:');
        $this->command->warn('    php artisan db:seed --class=ClearFarmOSTestDataSeeder');
    }

    private function createTestHarvestLogs()
    {
        $testHarvests = [
            [
                'farmos_id' => 'TEST-LOG-001',
                'farmos_asset_id' => 'TEST-ASSET-001',
                'crop_name' => 'TEST - Cherry Tomatoes',
                'crop_type' => 'tomato',
                'quantity' => 25.5,
                'units' => 'lbs',
                'harvest_date' => Carbon::now()->subDays(2),
                'location' => 'TEST Greenhouse 1',
                'notes' => 'TEST DATA - Beautiful harvest from greenhouse',
                'synced_to_stock' => false,
            ],
            [
                'farmos_id' => 'TEST-LOG-002',
                'farmos_asset_id' => 'TEST-ASSET-002',
                'crop_name' => 'TEST - Butter Lettuce',
                'crop_type' => 'lettuce',
                'quantity' => 30,
                'units' => 'bunches',
                'harvest_date' => Carbon::now()->subDays(1),
                'location' => 'TEST Field A',
                'notes' => 'TEST DATA - Ready for market',
                'synced_to_stock' => true,
            ],
            [
                'farmos_id' => 'TEST-LOG-003',
                'farmos_asset_id' => 'TEST-ASSET-003',
                'crop_name' => 'TEST - Fresh Basil',
                'crop_type' => 'herbs',
                'quantity' => 15,
                'units' => 'bunches',
                'harvest_date' => Carbon::now()->subDays(3),
                'location' => 'TEST High Tunnel',
                'notes' => 'TEST DATA - Aromatic and fresh',
                'synced_to_stock' => false,
            ],
        ];

        foreach ($testHarvests as $harvest) {
            HarvestLog::create($harvest);
        }

        $this->command->info('Created ' . count($testHarvests) . ' test harvest logs');
    }

    private function createTestStockItems()
    {
        $testStock = [
            [
                'name' => 'TEST - Cherry Tomatoes',
                'slug' => 'test-cherry-tomatoes',
                'crop_type' => 'tomato',
                'current_stock' => 45.5,
                'available_stock' => 45.5,
                'units' => 'lbs',
                'minimum_stock' => 20,
                'unit_price' => 6.50,
                'storage_location' => 'TEST Cold Storage',
                'description' => 'TEST DATA - In cold storage, ready for delivery',
                'is_active' => true,
            ],
            [
                'name' => 'TEST - Butter Lettuce',
                'slug' => 'test-butter-lettuce',
                'crop_type' => 'lettuce',
                'current_stock' => 18,
                'available_stock' => 18,
                'units' => 'bunches',
                'minimum_stock' => 25,
                'unit_price' => 3.00,
                'storage_location' => 'TEST Cold Storage',
                'description' => 'TEST DATA - LOW STOCK - Need to harvest more',
                'is_active' => true,
            ],
            [
                'name' => 'TEST - Fresh Carrots',
                'slug' => 'test-fresh-carrots',
                'crop_type' => 'carrot',
                'current_stock' => 0,
                'available_stock' => 0,
                'units' => 'lbs',
                'minimum_stock' => 15,
                'unit_price' => 4.00,
                'storage_location' => 'TEST Field B',
                'description' => 'TEST DATA - OUT OF STOCK - Ready to harvest',
                'is_active' => true,
            ],
        ];

        foreach ($testStock as $stock) {
            StockItem::create($stock);
        }

        $this->command->info('Created ' . count($testStock) . ' test stock items');
    }

    private function createTestCropPlans()
    {
        $testPlans = [
            [
                'crop_name' => 'TEST - Spring Lettuce',
                'crop_type' => 'lettuce',
                'variety' => 'TEST Mixed Greens',
                'planned_seeding_date' => Carbon::now()->addDays(5),
                'planned_transplant_date' => Carbon::now()->addDays(20),
                'planned_harvest_start' => Carbon::now()->addDays(45),
                'planned_harvest_end' => Carbon::now()->addDays(60),
                'planned_quantity' => 200,
                'quantity_units' => 'plants',
                'expected_yield' => 50,
                'yield_units' => 'bunches',
                'location' => 'TEST Greenhouse 2',
                'status' => 'planned',
                'notes' => 'TEST DATA - Spring succession planting',
            ],
            [
                'crop_name' => 'TEST - Summer Tomatoes',
                'crop_type' => 'tomato',
                'variety' => 'TEST Beefsteak',
                'planned_seeding_date' => Carbon::now()->subDays(15),
                'planned_transplant_date' => Carbon::now()->addDays(5),
                'planned_harvest_start' => Carbon::now()->addDays(60),
                'planned_harvest_end' => Carbon::now()->addDays(120),
                'actual_seeding_date' => Carbon::now()->subDays(15),
                'planned_quantity' => 48,
                'quantity_units' => 'plants',
                'expected_yield' => 75,
                'yield_units' => 'lbs',
                'location' => 'TEST High Tunnel',
                'status' => 'seeded',
                'notes' => 'TEST DATA - Seedlings looking strong',
            ],
            [
                'crop_name' => 'TEST - Fall Kale',
                'crop_type' => 'kale',
                'variety' => 'TEST Lacinato',
                'planned_seeding_date' => Carbon::now()->addDays(30),
                'planned_harvest_start' => Carbon::now()->addDays(90),
                'planned_harvest_end' => Carbon::now()->addDays(150),
                'planned_quantity' => 100,
                'quantity_units' => 'plants',
                'expected_yield' => 40,
                'yield_units' => 'bunches',
                'location' => 'TEST Field A',
                'status' => 'planned',
                'notes' => 'TEST DATA - Cold-hardy variety for fall harvest',
            ],
        ];

        foreach ($testPlans as $plan) {
            CropPlan::create($plan);
        }

        $this->command->info('Created ' . count($testPlans) . ' test crop plans');
    }
}
