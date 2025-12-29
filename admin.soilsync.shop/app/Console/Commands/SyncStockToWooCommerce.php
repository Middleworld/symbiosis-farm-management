<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\StockItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncStockToWooCommerce extends Command
{
    protected $signature = 'stock:sync-to-woocommerce 
                           {--product-id= : Sync specific product ID only}
                           {--dry-run : Preview changes without syncing}
                           {--force : Force sync even if recently synced}';
    
    protected $description = 'Sync local stock levels to WooCommerce product inventory';

    public function handle(): int
    {
        $this->info('🛒 Starting stock → WooCommerce sync...');
        
        $dryRun = $this->option('dry-run');
        $productId = $this->option('product-id');
        $force = $this->option('force');
        
        try {
            // Verify WordPress database connection exists
            if (!config('database.connections.wordpress')) {
                $this->error('❌ WordPress database connection not configured');
                $this->info('Add wordpress connection to config/database.php');
                return 1;
            }
            
            // Test connection
            DB::connection('wordpress')->getPdo();
            $this->line('✓ WordPress database connection verified');
            
            // Get stock items that need syncing
            $query = StockItem::where('is_active', true)
                ->where('track_stock', true)
                ->where('auto_sync_to_woo', true)
                ->whereNotNull('woocommerce_product_id');
            
            if ($productId) {
                $query->where('woocommerce_product_id', $productId);
            }
            
            if (!$force) {
                // Only sync if not synced in last 10 minutes (avoid excessive syncs)
                $query->where(function($q) {
                    $q->whereNull('last_woo_sync_at')
                      ->orWhere('last_woo_sync_at', '<', now()->subMinutes(10));
                });
            }
            
            $stockItems = $query->get();
            
            if ($stockItems->isEmpty()) {
                $this->info('✓ No stock items need syncing');
                $this->newLine();
                $this->info('💡 Tip: To enable WooCommerce sync for a product:');
                $this->line('   1. Set woocommerce_product_id on StockItem');
                $this->line('   2. Set auto_sync_to_woo = true');
                $this->line('   3. Ensure product exists in WooCommerce');
                return 0;
            }
            
            $this->info("Found {$stockItems->count()} stock items to sync");
            $this->newLine();
            
            $synced = 0;
            $skipped = 0;
            $errors = 0;
            
            foreach ($stockItems as $stockItem) {
                $result = $this->syncStockItem($stockItem, $dryRun);
                
                if ($result['synced']) $synced++;
                if ($result['skipped']) $skipped++;
                if ($result['error']) $errors++;
            }
            
            $this->newLine();
            $this->info('✅ Sync complete!');
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Products Synced', $synced],
                    ['Skipped', $skipped],
                    ['Errors', $errors],
                ]
            );
            
            if ($dryRun) {
                $this->warn('⚠ Dry run mode - no actual changes made to WooCommerce');
            }
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error('❌ Sync failed: ' . $e->getMessage());
            Log::error('WooCommerce stock sync failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }
    
    protected function syncStockItem(StockItem $stockItem, bool $dryRun): array
    {
        $result = ['synced' => false, 'skipped' => false, 'error' => false];
        
        try {
            $stockQuantity = (int) floor($stockItem->available_stock ?? $stockItem->current_stock);
            $productId = $stockItem->woocommerce_product_id;
            
            $this->line("  {$stockItem->name} (WC#{$productId}):");
            $this->line("    Current Stock: {$stockItem->current_stock} {$stockItem->units}");
            $this->line("    Available: {$stockItem->available_stock} {$stockItem->units}");
            $this->line("    → Syncing: {$stockQuantity} units");
            
            if ($dryRun) {
                $this->line("    [DRY RUN] Would update WooCommerce product");
                $result['synced'] = true;
                return $result;
            }
            
            // Check if product exists in WooCommerce
            $productExists = DB::connection('wordpress')
                ->table('posts')
                ->where('ID', $productId)
                ->where('post_type', 'product')
                ->exists();
            
            if (!$productExists) {
                $this->warn("    ⚠ Product not found in WooCommerce - skipping");
                $result['skipped'] = true;
                return $result;
            }
            
            // Update stock quantity
            $this->updateOrCreateMeta($productId, '_stock', $stockQuantity);
            
            // Update stock status
            $stockStatus = $stockQuantity > 0 ? 'instock' : 'outofstock';
            $this->updateOrCreateMeta($productId, '_stock_status', $stockStatus);
            
            // Update manage stock flag
            $this->updateOrCreateMeta($productId, '_manage_stock', 'yes');
            
            // Update last sync timestamp
            $stockItem->update(['last_woo_sync_at' => now()]);
            
            $this->info("    ✓ Synced successfully ({$stockStatus})");
            $result['synced'] = true;
            
            // Log the sync
            Log::info('Stock synced to WooCommerce', [
                'stock_item_id' => $stockItem->id,
                'product_id' => $productId,
                'quantity' => $stockQuantity,
                'status' => $stockStatus
            ]);
            
        } catch (\Exception $e) {
            $this->error("    ✗ Error: {$e->getMessage()}");
            Log::error('WooCommerce stock item sync failed', [
                'stock_item_id' => $stockItem->id ?? null,
                'product_id' => $productId ?? null,
                'error' => $e->getMessage()
            ]);
            $result['error'] = true;
        }
        
        return $result;
    }
    
    /**
     * Update or create WooCommerce product meta
     */
    protected function updateOrCreateMeta($productId, $metaKey, $metaValue): void
    {
        $exists = DB::connection('wordpress')
            ->table('postmeta')
            ->where('post_id', $productId)
            ->where('meta_key', $metaKey)
            ->exists();
        
        if ($exists) {
            DB::connection('wordpress')
                ->table('postmeta')
                ->where('post_id', $productId)
                ->where('meta_key', $metaKey)
                ->update(['meta_value' => $metaValue]);
        } else {
            DB::connection('wordpress')
                ->table('postmeta')
                ->insert([
                    'post_id' => $productId,
                    'meta_key' => $metaKey,
                    'meta_value' => $metaValue
                ]);
        }
    }
}
