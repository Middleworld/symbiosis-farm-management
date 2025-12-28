<?php
require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    // Check the timeline of scheduled subscription actions
    $oldest = DB::connection('wordpress')
        ->select('SELECT scheduled_date_gmt, args FROM D6sPMX_actionscheduler_actions WHERE hook = "woocommerce_scheduled_subscription_payment" AND status = "pending" ORDER BY scheduled_date_gmt ASC LIMIT 1');

    $newest = DB::connection('wordpress')
        ->select('SELECT scheduled_date_gmt, args FROM D6sPMX_actionscheduler_actions WHERE hook = "woocommerce_scheduled_subscription_payment" AND status = "pending" ORDER BY scheduled_date_gmt DESC LIMIT 1');

    if (!empty($oldest)) {
        echo "Oldest scheduled renewal: " . $oldest[0]->scheduled_date_gmt . "\n";
    }
    if (!empty($newest)) {
        echo "Newest scheduled renewal: " . $newest[0]->scheduled_date_gmt . "\n";
    }

    // Check for failed/cancelled actions
    $failed = DB::connection('wordpress')
        ->select('SELECT COUNT(*) as count FROM D6sPMX_actionscheduler_actions WHERE hook = "woocommerce_scheduled_subscription_payment" AND status = "failed"');

    $cancelled = DB::connection('wordpress')
        ->select('SELECT COUNT(*) as count FROM D6sPMX_actionscheduler_actions WHERE hook = "woocommerce_scheduled_subscription_payment" AND status = "cancelled"');

    echo "Failed renewal actions: " . $failed[0]->count . "\n";
    echo "Cancelled renewal actions: " . $cancelled[0]->count . "\n";

    // Check when subscriptions were created vs when their actions were scheduled
    $subscriptions = DB::connection('wordpress')
        ->select('SELECT ID, post_date FROM D6sPMX_posts WHERE post_type = "shop_subscription" AND post_status = "wc-active" ORDER BY post_date DESC LIMIT 5');

    echo "\nRecent subscription creation dates:\n";
    foreach ($subscriptions as $sub) {
        echo "Subscription #" . $sub->ID . " - Created: " . $sub->post_date . "\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
