#!/usr/bin/env php
<?php
/**
 * Merge Duplicate Subscriptions
 * 
 * This script merges multiple subscriptions per customer into a single active subscription.
 * It migrates all historical data (renewal orders, notes, metadata) from old subscriptions
 * to the current active one, preserving complete customer history.
 * 
 * PROBLEM:
 * - Customers like Philip Bauckham have 2+ subscriptions (old cancelled + new active)
 * - Old subscription has 11 renewal orders with complete payment history
 * - New subscription starts fresh with no history
 * - Results in incomplete customer records and confused delivery schedules
 * 
 * SOLUTION:
 * - Migrate all renewal orders from old subscriptions to active subscription
 * - Copy subscription notes to preserve history
 * - Update _subscription_renewal metadata on orders to point to active subscription
 * - Mark old subscriptions as "merged" in notes
 * - Preserve complete customer lifecycle in one subscription record
 * 
 * Usage: php merge-duplicate-subscriptions.php [--dry-run] [--customer-id=63]
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

// Parse command line arguments
$dryRun = in_array('--dry-run', $argv);
$customerIdFilter = null;

foreach ($argv as $arg) {
    if (strpos($arg, '--customer-id=') === 0) {
        $customerIdFilter = (int) str_replace('--customer-id=', '', $arg);
    }
}

echo "╔═══════════════════════════════════════════════════════════╗\n";
echo "║     Merge Duplicate Subscriptions - History Consolidation  ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n\n";

if ($dryRun) {
    echo "🔍 DRY RUN MODE - No changes will be made\n\n";
}

// Customers with active subscriptions to merge
$customers_to_merge = [
    ['email' => 'alexandrasbartlett@gmail.com', 'id' => 20, 'active' => 227613, 'old' => [224661]],
    ['email' => 'laurstratford@gmail.com', 'id' => 28, 'active' => 227571, 'old' => [224681]],
    ['email' => 'anderson.ben0405@gmail.com', 'id' => 60, 'active' => 225212, 'old' => [227663]],
    ['email' => 'sarah.denford@gmail.com', 'id' => 30, 'active' => 227578, 'old' => [225214]],
    ['email' => 'amiga4@hotmail.com', 'id' => 21, 'active' => 227624, 'old' => [224663]],
    ['email' => 'phil.bauckham@hotmail.co.uk', 'id' => 63, 'active' => 227977, 'old' => [225424]],
    ['email' => 'amyeastwood89@gmail.com', 'id' => 1453, 'active' => 228045, 'old' => [228043]],
];

// Filter by customer ID if specified
if ($customerIdFilter) {
    $customers_to_merge = array_filter($customers_to_merge, function($customer) use ($customerIdFilter) {
        return $customer['id'] === $customerIdFilter;
    });
    
    if (empty($customers_to_merge)) {
        echo "❌ No customer found with ID {$customerIdFilter}\n";
        exit(1);
    }
    
    echo "🎯 Filtering to customer ID {$customerIdFilter}\n\n";
}

$totalMerged = 0;
$totalOrdersMigrated = 0;
$totalNotesMigrated = 0;

foreach ($customers_to_merge as $customer) {
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "👤 Customer: {$customer['email']}\n";
    echo "📋 Active Subscription: #{$customer['active']}\n";
    echo "📦 Old Subscription(s): #" . implode(', #', $customer['old']) . "\n\n";
    
    $activeSubId = $customer['active'];
    
    foreach ($customer['old'] as $oldSubId) {
        echo "  🔄 Processing subscription #{$oldSubId}...\n";
        
        // Get subscription details
        $oldSub = DB::connection('wordpress')
            ->table('posts')
            ->where('ID', $oldSubId)
            ->first();
        
        if (!$oldSub) {
            echo "    ⚠️  Subscription not found, skipping\n\n";
            continue;
        }
        
        // 1. Find and migrate renewal orders
        $renewalOrders = DB::connection('wordpress')
            ->table('posts as p')
            ->join('postmeta as pm', 'p.ID', '=', 'pm.post_id')
            ->where('p.post_type', 'shop_order')
            ->where('pm.meta_key', '_subscription_renewal')
            ->where('pm.meta_value', $oldSubId)
            ->select('p.ID', 'p.post_status', 'p.post_date')
            ->orderBy('p.post_date', 'asc')
            ->get();
        
        if ($renewalOrders->count() > 0) {
            echo "    📄 Found {$renewalOrders->count()} renewal order(s) to migrate\n";
            
            foreach ($renewalOrders as $order) {
                $orderDate = Carbon::parse($order->post_date)->format('Y-m-d');
                echo "      • Order #{$order->ID} ({$order->post_status}) - {$orderDate}\n";
                
                if (!$dryRun) {
                    // Update _subscription_renewal to point to active subscription
                    DB::connection('wordpress')
                        ->table('postmeta')
                        ->where('post_id', $order->ID)
                        ->where('meta_key', '_subscription_renewal')
                        ->update(['meta_value' => $activeSubId]);
                    
                    // Add order note about the migration
                    $comment = [
                        'comment_post_ID' => $order->ID,
                        'comment_content' => "Renewal order migrated from old subscription #{$oldSubId} to active subscription #{$activeSubId} during merge process.",
                        'comment_type' => 'order_note',
                        'comment_author' => 'System',
                        'comment_author_email' => '',
                        'comment_date' => Carbon::now()->toDateTimeString(),
                        'comment_date_gmt' => Carbon::now('UTC')->toDateTimeString(),
                        'comment_approved' => 1,
                    ];
                    
                    DB::connection('wordpress')->table('comments')->insert($comment);
                    
                    $totalOrdersMigrated++;
                }
            }
        }
        
        // 2. Copy subscription notes from old to active subscription
        $notes = DB::connection('wordpress')
            ->table('comments')
            ->where('comment_post_ID', $oldSubId)
            ->where('comment_type', 'order_note')
            ->orderBy('comment_date', 'asc')
            ->get();
        
        if ($notes->count() > 0) {
            echo "    📝 Found {$notes->count()} subscription note(s) to copy\n";
            
            if (!$dryRun) {
                foreach ($notes as $note) {
                    // Copy note to active subscription with attribution
                    $newNote = [
                        'comment_post_ID' => $activeSubId,
                        'comment_content' => "[Historical note from subscription #{$oldSubId}] " . $note->comment_content,
                        'comment_type' => 'order_note',
                        'comment_author' => $note->comment_author,
                        'comment_author_email' => $note->comment_author_email,
                        'comment_date' => $note->comment_date,
                        'comment_date_gmt' => $note->comment_date_gmt,
                        'comment_approved' => 1,
                    ];
                    
                    DB::connection('wordpress')->table('comments')->insert($newNote);
                    $totalNotesMigrated++;
                }
            }
        }
        
        // 3. Add merge note to active subscription
        if (!$dryRun) {
            $mergeNote = [
                'comment_post_ID' => $activeSubId,
                'comment_content' => sprintf(
                    "Merged historical data from subscription #%d (created %s, %d renewal orders, %d notes). Complete customer history now consolidated under this subscription.",
                    $oldSubId,
                    Carbon::parse($oldSub->post_date)->format('Y-m-d'),
                    $renewalOrders->count(),
                    $notes->count()
                ),
                'comment_type' => 'order_note',
                'comment_author' => 'System - Merge Script',
                'comment_author_email' => '',
                'comment_date' => Carbon::now()->toDateTimeString(),
                'comment_date_gmt' => Carbon::now('UTC')->toDateTimeString(),
                'comment_approved' => 1,
            ];
            
            DB::connection('wordpress')->table('comments')->insert($mergeNote);
        }
        
        // 4. Add note to old subscription indicating it was merged
        if (!$dryRun) {
            $oldNote = [
                'comment_post_ID' => $oldSubId,
                'comment_content' => sprintf(
                    "This subscription's historical data (renewal orders, notes) has been merged into active subscription #%d. All future activity should reference subscription #%d.",
                    $activeSubId,
                    $activeSubId
                ),
                'comment_type' => 'order_note',
                'comment_author' => 'System - Merge Script',
                'comment_author_email' => '',
                'comment_date' => Carbon::now()->toDateTimeString(),
                'comment_date_gmt' => Carbon::now('UTC')->toDateTimeString(),
                'comment_approved' => 1,
            ];
            
            DB::connection('wordpress')->table('comments')->insert($oldNote);
        }
        
        // 5. Update old subscription metadata to mark as merged
        if (!$dryRun) {
            DB::connection('wordpress')
                ->table('postmeta')
                ->updateOrInsert(
                    ['post_id' => $oldSubId, 'meta_key' => '_merged_into_subscription'],
                    ['meta_value' => $activeSubId]
                );
            
            DB::connection('wordpress')
                ->table('postmeta')
                ->updateOrInsert(
                    ['post_id' => $oldSubId, 'meta_key' => '_merged_at'],
                    ['meta_value' => Carbon::now()->toDateTimeString()]
                );
        }
        
        echo "    ✅ Migration complete\n\n";
        $totalMerged++;
    }
    
    echo "✅ Customer merge complete!\n\n";
}

echo "╔═══════════════════════════════════════════════════════════╗\n";
echo "║                     SUMMARY                                ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n\n";

echo "📊 Statistics:\n";
echo "  • Customers processed: " . count($customers_to_merge) . "\n";
echo "  • Subscriptions merged: {$totalMerged}\n";
echo "  • Renewal orders migrated: {$totalOrdersMigrated}\n";
echo "  • Notes copied: {$totalNotesMigrated}\n\n";

if ($dryRun) {
    echo "ℹ️  This was a DRY RUN - no changes were made\n";
    echo "   Run without --dry-run to perform the merge\n\n";
} else {
    echo "✅ Merge complete! All customer histories consolidated.\n\n";
    echo "📋 Next Steps:\n";
    echo "  1. Verify delivery schedule shows correct data\n";
    echo "  2. Check WooCommerce admin - active subscriptions should show complete order history\n";
    echo "  3. Old subscriptions can be kept (marked as merged) or moved to trash\n\n";
}
