<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$emails = ['laurstratford@gmail.com', 'sarahmhenshaw@hotmail.com', 'trcy1066@aol.com'];

foreach($emails as $email) {
    echo str_repeat('=', 80) . PHP_EOL;
    echo "Customer: $email" . PHP_EOL;
    
    $wpUser = DB::connection('wordpress')->table('users')->where('user_email', $email)->first();
    if (!$wpUser) {
        echo "User not found!" . PHP_EOL;
        continue;
    }
    
    echo "WP User ID: {$wpUser->ID}" . PHP_EOL;
    
    $subs = DB::connection('wordpress')
        ->table('posts')
        ->where('post_type', 'shop_subscription')
        ->where('post_author', $wpUser->ID)
        ->whereIn('post_status', ['wc-active', 'wc-on-hold', 'wc-pending'])
        ->get();
    
    echo "Active subscriptions: " . $subs->count() . PHP_EOL;
    
    foreach($subs as $sub) {
        echo "  Sub #{$sub->ID} - Status: {$sub->post_status}" . PHP_EOL;
        
        $meta = DB::connection('wordpress')
            ->table('postmeta')
            ->where('post_id', $sub->ID)
            ->whereIn('meta_key', ['_payment_method', '_stripe_customer_id', '_stripe_source_id', '_schedule_next_payment', '_order_total'])
            ->get()
            ->pluck('meta_value', 'meta_key');
        
        echo "  Payment method: " . ($meta['_payment_method'] ?? 'none') . PHP_EOL;
        echo "  Stripe customer: " . ($meta['_stripe_customer_id'] ?? 'MISSING') . PHP_EOL;
        echo "  Stripe source: " . ($meta['_stripe_source_id'] ?? 'MISSING') . PHP_EOL;
        echo "  Order total: £" . ($meta['_order_total'] ?? '0') . PHP_EOL;
        echo "  Next payment: " . ($meta['_schedule_next_payment'] ?? 'NOT SCHEDULED') . PHP_EOL;
        
        // Check recent renewal orders
        $renewals = DB::connection('wordpress')
            ->table('posts as p')
            ->join('postmeta as pm', 'p.ID', '=', 'pm.post_id')
            ->where('pm.meta_key', '_subscription_renewal')
            ->where('pm.meta_value', $sub->ID)
            ->where('p.post_date', '>=', now()->subDays(7))
            ->select('p.ID', 'p.post_status', 'p.post_date')
            ->orderBy('p.post_date', 'desc')
            ->get();
        
        if ($renewals->count() > 0) {
            echo "  Recent renewal attempts:" . PHP_EOL;
            foreach($renewals as $renewal) {
                echo "    Order #{$renewal->ID} - {$renewal->post_status} - {$renewal->post_date}" . PHP_EOL;
            }
        } else {
            echo "  No recent renewal attempts" . PHP_EOL;
        }
    }
    echo PHP_EOL;
}
