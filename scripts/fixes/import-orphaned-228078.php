<?php
/**
 * Import orphaned WooCommerce subscription #228078 into Laravel admin
 * 
 * This subscription was created by POS but failed to save to vegbox_subscriptions table
 * due to transaction not covering WordPress database connection.
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== IMPORT ORPHANED SUBSCRIPTION #228078 ===\n\n";

// Fetch WooCommerce subscription
$wooSub = \App\Models\WooCommerceOrder::with(['meta', 'items'])->find(228078);

if (!$wooSub) {
    echo "❌ Subscription #228078 not found in WooCommerce\n";
    exit(1);
}

// Check if already imported
$existing = \App\Models\VegboxSubscription::where('woo_subscription_id', 228078)->first();
if ($existing) {
    echo "✅ Subscription #228078 already exists in vegbox_subscriptions (ID: {$existing->id})\n";
    echo "   No import needed.\n";
    exit(0);
}

echo "WooCommerce Subscription #228078:\n";
echo "  Status: {$wooSub->post_status}\n";
echo "  Date: {$wooSub->post_date}\n";
echo "  Customer: " . $wooSub->getMeta('_billing_first_name') . " " . $wooSub->getMeta('_billing_last_name') . "\n";
echo "  Email: " . $wooSub->getMeta('_billing_email') . "\n";
echo "  Total: £" . $wooSub->getMeta('_order_total') . "\n";
echo "  Billing: " . $wooSub->getMeta('_billing_interval') . " " . $wooSub->getMeta('_billing_period') . "\n\n";

echo "Creating VegboxSubscription record...\n";

try {
    $customerId = (int) $wooSub->getMeta('_customer_user');
    $interval = (int) $wooSub->getMeta('_billing_interval');
    $period = $wooSub->getMeta('_billing_period');
    $total = $wooSub->getMeta('_order_total');
    $nextPayment = $wooSub->getMeta('_schedule_next_payment');
    
    // Determine box type from line items
    $boxType = 'Vegbox Subscription';
    foreach ($wooSub->items as $item) {
        if (strpos($item->order_item_name, 'Vegbox') !== false) {
            $boxType = $item->order_item_name;
        }
    }
    
    // Create VegboxSubscription
    $vegboxSub = \App\Models\VegboxSubscription::create([
        'subscriber_id' => $customerId,
        'subscriber_type' => 'App\\Models\\Customer',
        'plan_id' => null,
        'slug' => 'imported-pos-' . $wooSub->ID,
        'name' => json_encode(['en' => $boxType]),
        'description' => json_encode(['en' => 'Imported from POS - WooCommerce Sub #' . $wooSub->ID]),
        'price' => $total,
        'currency' => 'GBP',
        'starts_at' => $wooSub->post_date,
        'billing_frequency' => $interval,
        'billing_period' => $period,
        'next_billing_at' => $nextPayment ?: now()->addWeeks($interval),
        'box_type' => $boxType,
        'box_size' => 'large',
        'frequency' => $interval == 1 ? 'week' : '2 weeks',
        'status' => 'active',
        'delivery_method' => 'collection',
        'woo_subscription_id' => $wooSub->ID,
        'imported_from_woo' => true,
        'next_delivery_date' => $nextPayment ?: now()->next('Thursday'),
    ]);
    
    echo "\n✅ SUCCESS!\n";
    echo "   Created VegboxSubscription ID: {$vegboxSub->id}\n";
    echo "   Linked to WooCommerce subscription #228078\n";
    echo "   Status: {$vegboxSub->status}\n";
    echo "   Next billing: " . $vegboxSub->next_billing_at->format('Y-m-d') . "\n\n";
    
    echo "This subscription will now appear in the Laravel admin interface.\n";
    
} catch (\Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n=== IMPORT COMPLETE ===\n";
