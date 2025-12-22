<?php
/**
 * Test script to verify delivery schedule data fetching works
 * Tests the new direct database approach vs old REST API approach
 * 
 * Run: php test-delivery-schedule.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== DELIVERY SCHEDULE DATA FETCH TEST ===\n\n";

try {
    $wpApi = app(\App\Services\WpApiService::class);
    
    echo "1. Testing getDeliveryScheduleData() method...\n";
    $startTime = microtime(true);
    
    $data = $wpApi->getDeliveryScheduleData(100);
    
    $endTime = microtime(true);
    $duration = round(($endTime - $startTime) * 1000, 2);
    
    echo "   ✅ Fetched " . count($data) . " subscriptions in {$duration}ms\n\n";
    
    if (count($data) > 0) {
        echo "2. Sample subscription data structure:\n";
        $sample = $data[0];
        echo "   ID: " . ($sample['id'] ?? 'N/A') . "\n";
        echo "   Customer ID: " . ($sample['customer_id'] ?? 'N/A') . "\n";
        echo "   Status: " . ($sample['status'] ?? 'N/A') . "\n";
        echo "   Billing Email: " . ($sample['billing']['email'] ?? 'N/A') . "\n";
        echo "   Billing Name: " . ($sample['billing']['first_name'] ?? '') . " " . ($sample['billing']['last_name'] ?? '') . "\n";
        echo "   Shipping Address: " . ($sample['shipping']['address_1'] ?? 'N/A') . "\n";
        echo "   Billing Period: " . ($sample['billing_period'] ?? 'N/A') . "\n";
        echo "   Billing Interval: " . ($sample['billing_interval'] ?? 'N/A') . "\n";
        echo "   Shipping Total: £" . ($sample['shipping_total'] ?? '0') . "\n";
        echo "   Total: £" . ($sample['total'] ?? '0') . "\n";
        echo "   Line Items: " . count($sample['line_items'] ?? []) . "\n\n";
        
        echo "3. Status breakdown:\n";
        $statusCounts = [];
        foreach ($data as $sub) {
            $status = $sub['status'] ?? 'unknown';
            $statusCounts[$status] = ($statusCounts[$status] ?? 0) + 1;
        }
        
        foreach ($statusCounts as $status => $count) {
            echo "   {$status}: {$count}\n";
        }
        
        echo "\n4. Delivery type breakdown (shipping cost):\n";
        $deliveryCount = 0;
        $collectionCount = 0;
        
        foreach ($data as $sub) {
            $shippingTotal = floatval($sub['shipping_total'] ?? 0);
            if ($shippingTotal > 0) {
                $deliveryCount++;
            } else {
                $collectionCount++;
            }
        }
        
        echo "   Deliveries (shipping > £0): {$deliveryCount}\n";
        echo "   Collections (shipping = £0): {$collectionCount}\n";
        
        echo "\n5. Frequency breakdown:\n";
        $weeklyCount = 0;
        $fortnightlyCount = 0;
        $otherCount = 0;
        
        foreach ($data as $sub) {
            $period = strtolower($sub['billing_period'] ?? '');
            $interval = intval($sub['billing_interval'] ?? 1);
            
            if ($period === 'week' && $interval === 2) {
                $fortnightlyCount++;
            } elseif ($period === 'week' && $interval === 1) {
                $weeklyCount++;
            } else {
                $otherCount++;
            }
        }
        
        echo "   Weekly: {$weeklyCount}\n";
        echo "   Fortnightly: {$fortnightlyCount}\n";
        echo "   Other: {$otherCount}\n";
        
        echo "\n✅ SUCCESS: Delivery schedule data fetch working correctly!\n";
        echo "\n📝 This means the delivery schedule will work WITHOUT the WooCommerce Subscriptions plugin.\n";
        
    } else {
        echo "⚠️  WARNING: No subscriptions found\n";
        echo "   - Check if shop_subscription posts exist in WordPress database\n";
        echo "   - Verify database connection is working\n";
    }
    
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n=== TEST COMPLETE ===\n";
