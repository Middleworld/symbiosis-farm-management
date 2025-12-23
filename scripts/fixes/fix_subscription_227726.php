<?php
require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    // Check existing scheduled actions for subscription 227726
    $existing = DB::connection('wordpress')
        ->select('SELECT * FROM D6sPMX_actionscheduler_actions WHERE args LIKE "%227726%"');
    
    echo "Existing actions for subscription 227726: " . count($existing) . "\n";
    
    if (empty($existing)) {
        // Create a new scheduled action for next payment (monthly)
        $nextPayment = date('Y-m-d H:i:s', strtotime('+1 month'));
        
        DB::connection('wordpress')->insert(
            'INSERT INTO D6sPMX_actionscheduler_actions 
             (hook, status, args, scheduled_date_gmt, scheduled_date_local, 
              schedule, group_id, attempts, last_attempt_gmt, last_attempt_local, 
              claim_id, extended_args, priority) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                'woocommerce_scheduled_subscription_payment',
                'pending',
                '{"subscription_id":227726}',
                $nextPayment,
                $nextPayment,
                'a:2:{s:9:"recurring";s:5:"1 month";s:4:"next";s:19:"'.$nextPayment.'";}',
                1,
                0,
                '1970-01-01 00:00:00',
                '1970-01-01 00:00:00',
                0,
                NULL,
                10
            ]
        );
        
        echo "✅ Created scheduled payment action for subscription 227726\n";
        echo "Next payment scheduled: $nextPayment\n";
    } else {
        echo "Scheduled action already exists\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
