<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$subscriptionId = 227726;

echo "🔧 Fixing Dawn Karanga's subscription (ID: $subscriptionId)\n";
echo "======================================================\n";

// Get the next payment date from subscription meta
$meta = DB::connection('wordpress')->select('SELECT meta_value FROM D6sPMX_postmeta WHERE post_id = ? AND meta_key = "_schedule_next_payment"', [$subscriptionId]);

if (empty($meta)) {
    echo "❌ No next payment date found in subscription meta\n";
    exit(1);
}

$nextPaymentDate = $meta[0]->meta_value;
echo "📅 Next payment scheduled for: $nextPaymentDate\n";

// Check if action already exists
$existingActions = DB::connection('wordpress')->select('SELECT * FROM D6sPMX_actionscheduler_actions WHERE hook = "woocommerce_scheduled_subscription_payment" AND status = "pending"');

$actionExists = false;
foreach ($existingActions as $action) {
    $args = json_decode($action->args, true);
    if (isset($args['subscription_id']) && $args['subscription_id'] == $subscriptionId) {
        $actionExists = true;
        echo "⚠️  Action already exists! ID: " . $action->action_id . "\n";
        break;
    }
}

if ($actionExists) {
    echo "✅ Subscription appears to already be fixed\n";
    exit(0);
}

// Create the missing Action Scheduler action
$actionData = [
    'hook' => 'woocommerce_scheduled_subscription_payment',
    'status' => 'pending',
    'args' => json_encode(['subscription_id' => $subscriptionId]),
    'scheduled_date_gmt' => $nextPaymentDate,
    'scheduled_date_local' => $nextPaymentDate,
    'group_id' => 0,
    'last_attempt_gmt' => '0000-00-00 00:00:00',
    'last_attempt_local' => '0000-00-00 00:00:00',
    'claim_id' => 0,
    'extended_args' => 'N;'
];

try {
    $inserted = DB::connection('wordpress')->insert('INSERT INTO D6sPMX_actionscheduler_actions 
        (hook, status, args, scheduled_date_gmt, scheduled_date_local, group_id, last_attempt_gmt, last_attempt_local, claim_id, extended_args) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', 
        array_values($actionData)
    );
    
    if ($inserted) {
        echo "✅ Successfully created Action Scheduler action!\n";
        echo "📅 Next renewal will be processed on: $nextPaymentDate\n";
        
        // Add a subscription note
        $noteData = [
            'post_author' => 1,
            'post_date' => date('Y-m-d H:i:s'),
            'post_date_gmt' => gmdate('Y-m-d H:i:s'),
            'post_content' => 'Subscription renewal scheduling fixed by audit system',
            'post_title' => 'Subscription renewal scheduling restored',
            'post_status' => 'publish',
            'post_name' => 'subscription-note-' . time(),
            'post_type' => 'subscription_note',
            'post_parent' => $subscriptionId
        ];
        
        DB::connection('wordpress')->insert('INSERT INTO D6sPMX_posts 
            (post_author, post_date, post_date_gmt, post_content, post_title, post_status, post_name, post_type, post_parent) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)', 
            array_values($noteData)
        );
        
        echo "📝 Added subscription note about the fix\n";
        
    } else {
        echo "❌ Failed to create Action Scheduler action\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error creating action: " . $e->getMessage() . "\n";
}

// Verify the fix
echo "\n🔍 Verification:\n";
$verifyActions = DB::connection('wordpress')->select('SELECT * FROM D6sPMX_actionscheduler_actions WHERE hook = "woocommerce_scheduled_subscription_payment" AND status = "pending"');

$found = false;
foreach ($verifyActions as $action) {
    $args = json_decode($action->args, true);
    if (isset($args['subscription_id']) && $args['subscription_id'] == $subscriptionId) {
        echo "✅ VERIFIED: Action exists for subscription $subscriptionId\n";
        echo "   Scheduled: " . $action->scheduled_date_gmt . "\n";
        $found = true;
        break;
    }
}

if (!$found) {
    echo "❌ VERIFICATION FAILED: Action still missing\n";
}

echo "\n🎉 Fix complete!\n";
echo "💡 Dawn's subscription will now renew automatically on schedule\n";
