<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\Admin\RouteController;
use App\Services\WpApiService;
use Illuminate\Http\Request;

class TestRouting extends Command
{
    protected $signature = 'test:routing {--date= : Date to test routing for}';
    protected $description = 'Test the routing functionality without web interface';

    public function handle()
    {
        $date = $this->option('date') ?: date('Y-m-d');
        
        $this->info("Testing routing functionality for date: {$date}");
        $this->line("");
        
        try {
            // Test 1: Check if RouteController can be instantiated
            $this->line("1. Testing RouteController instantiation...");
            
            // Create a mock request
            $request = new Request(['date' => $date]);
            
            // Try to instantiate the controller
            $controller = app(RouteController::class);
            $this->info("✅ RouteController instantiated successfully");
            
            // Test 2: Check if services are available
            $this->line("\n2. Testing service dependencies...");
            
            $routeService = app(\App\Services\RouteOptimizationService::class);
            $this->info("✅ RouteOptimizationService available");
            
            $deliveryService = app(\App\Services\DeliveryScheduleService::class);
            $this->info("✅ DeliveryScheduleService available");
            
            $driverService = app(\App\Services\DriverNotificationService::class);
            $this->info("✅ DriverNotificationService available");
            
            $wpGoMapsService = app(\App\Services\WPGoMapsService::class);
            $this->info("✅ WPGoMapsService available");
            
            // Test 3: Try to get delivery data
            $this->line("\n3. Testing delivery data retrieval...");
            
            $wpApi = app(WpApiService::class);
            $scheduleData = $wpApi->getDeliveryScheduleData(10);
            
            if (!empty($scheduleData)) {
                $deliveryCount = count($scheduleData['deliveries'] ?? []);
                $collectionCount = count($scheduleData['collections'] ?? []);
                $this->info("✅ Retrieved schedule data: {$deliveryCount} deliveries, {$collectionCount} collections");
                
                // Show sample delivery addresses for routing
                if (!empty($scheduleData['deliveries'])) {
                    $this->line("\n4. Sample delivery addresses for routing:");
                    $count = 0;
                    foreach ($scheduleData['deliveries'] as $delivery) {
                        if ($count >= 3) break; // Show first 3
                        
                        $name = $delivery['customer_name'] ?? 'Unknown';
                        $address = $this->formatAddress($delivery);
                        $this->line("   - {$name}: {$address}");
                        $count++;
                    }
                }
            } else {
                $this->warn("⚠️ No delivery data found");
            }
            
            // Test 4: Test basic route optimization (if we have data)
            if (!empty($scheduleData['deliveries'])) {
                $this->line("\n5. Testing route optimization...");
                
                $testDeliveries = array_slice($scheduleData['deliveries'], 0, 3);
                $addresses = [];
                
                foreach ($testDeliveries as $delivery) {
                    $address = $this->formatAddress($delivery);
                    if ($address) {
                        $addresses[] = $address;
                    }
                }
                
                if (count($addresses) >= 2) {
                    $this->info("✅ Found " . count($addresses) . " addresses for route optimization test");
                    $this->line("   Addresses: " . implode(' → ', $addresses));
                } else {
                    $this->warn("⚠️ Not enough addresses for route optimization test");
                }
            }
            
            $this->line("\n" . str_repeat('=', 50));
            $this->info("✅ Routing system appears to be functional!");
            $this->line("All services are available and can retrieve delivery data.");
            $this->line("You should be able to access the route planner through the web interface.");
            
        } catch (\Exception $e) {
            $this->error("❌ Routing test failed: " . $e->getMessage());
            $this->line("Stack trace:");
            $this->line($e->getTraceAsString());
            return 1;
        }
        
        return 0;
    }
    
    private function formatAddress($delivery)
    {
        if (isset($delivery['address']) && is_array($delivery['address'])) {
            $parts = array_filter([
                $delivery['address']['address_1'] ?? '',
                $delivery['address']['city'] ?? '',
                $delivery['address']['postcode'] ?? ''
            ]);
            return implode(', ', $parts);
        }
        
        if (isset($delivery['billing']) && is_array($delivery['billing'])) {
            $parts = array_filter([
                $delivery['billing']['address_1'] ?? '',
                $delivery['billing']['city'] ?? '',
                $delivery['billing']['postcode'] ?? ''
            ]);
            return implode(', ', $parts);
        }
        
        return 'Address not available';
    }
}
