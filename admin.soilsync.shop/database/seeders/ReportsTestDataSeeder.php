<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportsTestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Clearing existing test data...');
        
        // Clear existing test data
        DB::table('delivery_completions')->where('notes', 'TEST DATA - Successful delivery')->delete();
        DB::table('farm_tasks')->where('title', 'LIKE', 'TEST -%')->delete();
        DB::table('harvest_logs')->where('crop_name', 'LIKE', 'TEST -%')->delete();
        DB::table('customer_week_assignments')->where('notes', 'TEST DATA - Week assignment')->delete();
        
        $this->command->info('Creating test data for reports...');

        // Create test delivery completions
        $this->createDeliveryCompletions();

        // Create test farm tasks
        $this->createFarmTasks();

        // Create test harvest logs
        $this->createHarvestLogs();

        // Create test customer week assignments
        $this->createCustomerAssignments();

        $this->command->info('✅ Reports test data created successfully!');
        $this->command->warn('⚠️  This is test data for development purposes only');
    }

    private function createDeliveryCompletions()
    {
        $deliveries = [];
        $customers = [
            ['name' => 'John Smith', 'email' => 'john@example.com'],
            ['name' => 'Sarah Johnson', 'email' => 'sarah@example.com'],
            ['name' => 'Mike Wilson', 'email' => 'mike@example.com'],
            ['name' => 'Emma Davis', 'email' => 'emma@example.com'],
            ['name' => 'Tom Brown', 'email' => 'tom@example.com'],
        ];

        for ($i = 0; $i < 50; $i++) {
            $customer = $customers[array_rand($customers)];
            $created = Carbon::now()->subDays(rand(0, 30));
            $completed = $created->copy()->addMinutes(rand(30, 180));

            $deliveries[] = [
                'external_id' => 'TEST-' . str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                'type' => 'delivery',
                'customer_name' => $customer['name'],
                'customer_email' => $customer['email'],
                'delivery_date' => $created->toDateString(),
                'completed_at' => $completed,
                'completed_by' => 'Test User',
                'notes' => 'TEST DATA - Successful delivery',
                'created_at' => $created,
                'updated_at' => $completed,
            ];
        }

        DB::table('delivery_completions')->insert($deliveries);
        $this->command->info('Created ' . count($deliveries) . ' test delivery completions');
    }

    private function createFarmTasks()
    {
        $tasks = [
            ['title' => 'TEST - Plant tomato seedlings', 'status' => 'completed'],
            ['title' => 'TEST - Water greenhouse beds', 'status' => 'completed'],
            ['title' => 'TEST - Harvest lettuce', 'status' => 'in_progress'],
            ['title' => 'TEST - Prepare delivery boxes', 'status' => 'todo'],
            ['title' => 'TEST - Check irrigation system', 'status' => 'completed'],
            ['title' => 'TEST - Weed vegetable beds', 'status' => 'completed'],
            ['title' => 'TEST - Transplant basil', 'status' => 'todo'],
            ['title' => 'TEST - Clean harvesting tools', 'status' => 'completed'],
            ['title' => 'TEST - Update planting records', 'status' => 'in_progress'],
            ['title' => 'TEST - Order new seeds', 'status' => 'todo'],
        ];

        $taskRecords = [];
        foreach ($tasks as $task) {
            $created = Carbon::now()->subDays(rand(0, 14));
            $taskRecords[] = [
                'type' => 'farm',
                'title' => $task['title'],
                'description' => 'TEST DATA - Farm maintenance task',
                'status' => $task['status'],
                'priority' => 'medium',
                'created_by' => 1,
                'created_at' => $created,
                'updated_at' => $created->copy()->addHours(rand(1, 48)),
            ];
        }

        DB::table('farm_tasks')->insert($taskRecords);
        $this->command->info('Created ' . count($taskRecords) . ' test farm tasks');
    }

    private function createHarvestLogs()
    {
        $harvests = [
            ['crop_name' => 'TEST - Cherry Tomatoes', 'quantity' => 25.5, 'units' => 'lbs'],
            ['crop_name' => 'TEST - Butter Lettuce', 'quantity' => 30, 'units' => 'bunches'],
            ['crop_name' => 'TEST - Fresh Basil', 'quantity' => 15, 'units' => 'bunches'],
            ['crop_name' => 'TEST - Carrots', 'quantity' => 40, 'units' => 'lbs'],
            ['crop_name' => 'TEST - Spinach', 'quantity' => 20, 'units' => 'bags'],
            ['crop_name' => 'TEST - Radishes', 'quantity' => 35, 'units' => 'bunches'],
            ['crop_name' => 'TEST - Kale', 'quantity' => 18, 'units' => 'bunches'],
            ['crop_name' => 'TEST - Swiss Chard', 'quantity' => 22, 'units' => 'bunches'],
        ];

        $harvestRecords = [];
        foreach ($harvests as $index => $harvest) {
            $harvestRecords[] = [
                'farmos_id' => 'TEST-HARVEST-' . str_pad($index + 1, 3, '0', STR_PAD_LEFT),
                'farmos_asset_id' => 'TEST-ASSET-' . str_pad($index + 1, 3, '0', STR_PAD_LEFT),
                'crop_name' => $harvest['crop_name'],
                'quantity' => $harvest['quantity'],
                'units' => $harvest['units'],
                'harvest_date' => Carbon::now()->subDays(rand(0, 30))->toDateString(),
                'created_at' => Carbon::now()->subDays(rand(0, 30)),
                'updated_at' => Carbon::now()->subDays(rand(0, 30)),
            ];
        }

        DB::table('harvest_logs')->insert($harvestRecords);
        $this->command->info('Created ' . count($harvestRecords) . ' test harvest logs');
    }

    private function createCustomerAssignments()
    {
        $customers = [
            ['email' => 'john@example.com'],
            ['email' => 'sarah@example.com'],
            ['email' => 'mike@example.com'],
            ['email' => 'emma@example.com'],
            ['email' => 'tom@example.com'],
            ['email' => 'lisa@example.com'],
            ['email' => 'david@example.com'],
        ];

        $assignments = [];
        foreach ($customers as $customer) {
            $assignments[] = [
                'subscription_id' => rand(1000, 9999),
                'customer_email' => $customer['email'],
                'assigned_week' => rand(0, 1) ? 'A' : 'B',
                'assignment_type' => rand(0, 1) ? 'manual' : 'auto',
                'assigned_by' => 1,
                'notes' => 'TEST DATA - Week assignment',
                'created_at' => Carbon::now()->subDays(rand(0, 30)),
                'updated_at' => Carbon::now()->subDays(rand(0, 30)),
            ];
        }

        DB::table('customer_week_assignments')->insert($assignments);
        $this->command->info('Created ' . count($assignments) . ' test customer assignments');
    }
}