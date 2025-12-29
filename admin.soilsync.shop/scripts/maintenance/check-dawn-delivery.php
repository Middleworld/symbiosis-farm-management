<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Find Dawn Karanga
$customer = \App\Models\Customer::where('name', 'like', '%Dawn%')
    ->orWhere('name', 'like', '%Karanga%')
    ->first();

if (!$customer) {
    echo "Customer 'Dawn Karanga' not found!\n";
    exit;
}

echo "CUSTOMER: {$customer->name}\n";
echo "Email: {$customer->email}\n";
echo "Phone: {$customer->phone}\n";
echo "Status: " . ($customer->is_active ? 'ACTIVE' : 'INACTIVE') . "\n";
echo str_repeat('=', 80) . "\n\n";

// Check subscriptions
$subscriptions = $customer->subscriptions()->get();
echo "SUBSCRIPTIONS (" . $subscriptions->count() . "):\n";
foreach ($subscriptions as $sub) {
    echo "  - {$sub->product->name}: " . ($sub->is_active ? 'ACTIVE' : 'INACTIVE/PAUSED');
    if ($sub->paused_at) {
        echo " (Paused: {$sub->paused_at})";
    }
    echo "\n";
}

// Check recent orders
echo "\nRECENT ORDERS:\n";
$orders = $customer->orders()->orderBy('delivery_date', 'desc')->take(5)->get();
foreach ($orders as $order) {
    echo "  {$order->delivery_date}: £{$order->total_amount} - {$order->status}";
    if ($order->notes) {
        echo " | Notes: {$order->notes}";
    }
    echo "\n";
}

// Check today's orders specifically
$today = now()->format('Y-m-d');
$todayOrders = $customer->orders()->where('delivery_date', $today)->get();
echo "\nTODAY'S ORDERS ($today): " . $todayOrders->count() . "\n";
foreach ($todayOrders as $order) {
    echo "  Order #{$order->id}: £{$order->total_amount} - {$order->status}\n";
}
