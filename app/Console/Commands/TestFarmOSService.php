<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FarmOSApi;
use App\Http\Controllers\Admin\FarmOSDataController;

class TestFarmOSService extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:farmos-service';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test FarmOS API Service methods';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing FarmOS API Service...');
        
        try {
            // Test direct instantiation
            $service = new FarmOSApi();
            $this->info('✓ FarmOSApi instantiated successfully');
            
            // Test method existence
            $hasCropPlanningData = method_exists($service, 'getCropPlanningData');
            $this->info("✓ getCropPlanningData method exists: " . ($hasCropPlanningData ? 'Yes' : 'No'));
            
            // Test service injection via Laravel container
            $serviceFromContainer = app(FarmOSApi::class);
            $this->info('✓ FarmOSApi resolved from container');
            
            // Test controller instantiation
            $controller = new FarmOSDataController($serviceFromContainer);
            $this->info('✓ FarmOSDataController instantiated with service');
            
            // Test method call
            $data = $serviceFromContainer->getCropPlanningData();
            $this->info('✓ getCropPlanningData() called successfully');
            $this->info('Data returned: ' . (is_array($data) ? count($data) . ' items' : 'Non-array data'));
            
        } catch (\Exception $e) {
            $this->error('✗ Error: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
        }
        
        return 0;
    }
}
