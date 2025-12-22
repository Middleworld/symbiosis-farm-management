<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$subscriptionId = 227726;

echo "Checking subscription $subscriptionId (Dawn Karanga)\n";
echo "================================================\n";

// Check subscription status
$sub = DB::connection('wordpress')->select('SELECT post_date, post_status FROM D6sPMX_posts WHERE ID = ? AND post_type = "shop_subscription"', [$subscriptionId]);

if (empty($sub)) {
    echo "❌ Subscription not found!\n";
    exit(1);
}

echo "✅ Subscription found:\n";
echo "   Status: " . $sub[0]->post_status . "\n";
echo "   Created: " . $sub[0]->post_date . "\n";

// Check for scheduled actions
$actions = DB::connection('wordpress')->select('SELECT hook, status, scheduled_date_gmt, args FROM D6sPMX_actionscheduler_actions WHERE hook = "woocommerce_scheduled_subscription_payment" AND status = "pending"');

$found = false;
foreach ($actions as $action) {
    $args = json_decode($action->args, true);
    if (isset($args['subscription_id']) && $args['subscription_id'] == $subscriptionId) {
        echo "\n✅ FOUND SCHEDULED ACTION:\n";
        echo "   Status: " . $action->status . "\n";
        echo "   Scheduled: " . $action->scheduled_date_gmt . "\n";
        echo "   Args: " . $action->args . "\n";
        $found = true;
        break;
    }
}

if (!$found) {
    echo "\n❌ NO SCHEDULED ACTION FOUND - This is why it's broken!\n";
}

echo "\nTotal pending subscription actions: " . count($actions) . "\n";

// Check subscription meta
echo "\n📋 Subscription Details:\n";
$meta = DB::connection('wordpress')->select('SELECT meta_key, meta_value FROM D6sPMX_postmeta WHERE post_id = ? AND meta_key IN ("_billing_first_name", "_billing_last_name", "_order_total", "_billing_period", "_billing_interval")', [$subscriptionId]);

foreach ($meta as $item) {
    echo "   " . $item->meta_key . ": " . $item->meta_value . "\n";
}

echo "\n🔍 Analysis:\n";
if ($found) {
    echo "✅ Subscription appears to be working correctly\n";
} else {
    echo "❌ Subscription is missing its renewal schedule - this needs to be fixed!\n";
    echo "💡 The audit system detected this as a broken subscription\n";
}
