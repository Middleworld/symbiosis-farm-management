<?php
/**
 * Test delivery vs collection classification
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== DELIVERY vs COLLECTION CLASSIFICATION TEST ===\n\n";

$wpApi = app(\App\Services\WpApiService::class);
$data = $wpApi->getDeliveryScheduleData(30);

$deliveries = 0;
$collections = 0;

foreach ($data as $sub) {
    if ($sub['status'] === 'active') {
        // Replicate the classification logic
        $type = 'collections'; // default
        
        // Check line items first
        foreach ($sub['line_items'] ?? [] as $item) {
            if (isset($item['name'])) {
                $itemName = strtolower($item['name']);
                
                // Collection indicators
                if (strpos($itemName, 'collection') !== false || 
                    strpos($itemName, 'colection') !== false ||
                    strpos($itemName, 'collect') !== false ||
                    strpos($itemName, 'pickup') !== false) {
                    $type = 'collections';
                    break;
                }
                
                // Delivery indicators
                if (strpos($itemName, 'delivery') !== false || 
                    strpos($itemName, 'shipping') !== false) {
                    $type = 'deliveries';
                    break;
                }
            }
        }
        
        // If no match in line items, check shipping total
        if ($type === 'collections' && floatval($sub['shipping_total'] ?? 0) > 0) {
            $type = 'deliveries';
        }
        
        $name = trim(($sub['billing']['first_name'] ?? '') . ' ' . ($sub['billing']['last_name'] ?? ''));
        $shipping = $sub['shipping_total'] ?? '0';
        
        $items = [];
        foreach ($sub['line_items'] ?? [] as $item) {
            $items[] = $item['name'];
        }
        
        $typeLabel = strtoupper(str_replace(['deliveries', 'collections'], ['DELIVERY', 'COLLECTION'], $type));
        
        echo "{$name}: {$typeLabel} (Shipping: £{$shipping})\n";
        echo "  Items: " . implode(', ', $items) . "\n\n";
        
        if ($type === 'deliveries') {
            $deliveries++;
        } else {
            $collections++;
        }
    }
}

echo "---\n";
echo "✅ Total: {$deliveries} deliveries, {$collections} collections\n";
echo "\n=== TEST COMPLETE ===\n";
