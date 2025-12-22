<?php
require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    // Get all active subscription IDs
    $activeSubs = DB::connection('wordpress')->select('SELECT ID FROM D6sPMX_posts WHERE post_type = "shop_subscription" AND post_status = "wc-active"');
    $activeIds = array_column($activeSubs, 'ID');
    
    echo "Active subscriptions: " . count($activeIds) . "\n";
    echo "IDs: " . implode(', ', $activeIds) . "\n\n";
    
    // Get subscription IDs that have scheduled renewal actions
    $withActions = DB::connection('wordpress')->select('SELECT DISTINCT CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(args, \'"subscription_id":\', -1), \'}\', 1) AS UNSIGNED) as sub_id FROM D6sPMX_actionscheduler_actions WHERE hook = "woocommerce_scheduled_subscription_payment" AND status = "pending"');
    $scheduledIds = array_column($withActions, 'sub_id');
    
    echo "Subscriptions with scheduled renewals: " . count($scheduledIds) . "\n";
    echo "IDs: " . implode(', ', $scheduledIds) . "\n\n";
    
    // Find missing ones
    $missing = array_diff($activeIds, $scheduledIds);
    echo "Broken subscriptions (no renewal scheduling): " . count($missing) . "\n";
    if (!empty($missing)) {
        echo "IDs: " . implode(', ', $missing) . "\n\n";
        
        // Get details of broken subscriptions
        echo "Details of broken subscriptions:\n";
        foreach ($missing as $id) {
            $sub = DB::connection('wordpress')->select('SELECT post_date FROM D6sPMX_posts WHERE ID = ?', [$id]);
            if (!empty($sub)) {
                echo "Subscription #$id - Created: " . $sub[0]->post_date . "\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
