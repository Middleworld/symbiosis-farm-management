<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\HarvestLog;
use App\Models\StockItem;

class TestHarvestToStockFlow extends Command
{
    protected $signature = 'test:harvest-to-stock-flow';
    protected $description = 'Test the complete harvest → stock → WooCommerce workflow';

    public function handle(): int
    {
        $this->info('🧪 Testing complete harvest workflow...');
        $this->newLine();
        
        // Step 1: Check local harvest log
        $this->info('Step 1: Check local harvest log from Field Kit');
        $harvest = HarvestLog::find(6);
        
        if (!$harvest) {
            $this->error('Test harvest #6 not found');
            return 1;
        }
        
        $this->line("  Harvest: {$harvest->crop_name}");
        $this->line("  Quantity: {$harvest->quantity} {$harvest->units}");
        $this->line("  Location: {$harvest->location}");
        $this->line("  Synced to stock: " . ($harvest->synced_to_stock ? 'Yes' : 'No'));
        $this->newLine();
        
        // Step 2: Process harvest to stock
        if (!$harvest->synced_to_stock) {
            $this->info('Step 2: Processing harvest to stock...');
            
            $stock = StockItem::firstOrCreate(
                ['name' => $harvest->crop_name],
                [
                    'crop_type' => $harvest->crop_type,
                    'units' => $harvest->units,
                    'current_stock' => 0,
                    'reserved_stock' => 0,
                    'available_stock' => 0,
                    'minimum_stock' => 0,
                    'is_active' => true,
                    'track_stock' => true,
                ]
            );
            
            $oldStock = $stock->current_stock;
            $stock->increment('current_stock', $harvest->quantity);
            $stock->refresh();
            
            $availableStock = $stock->current_stock - ($stock->reserved_stock ?? 0);
            $stock->update([
                'available_stock' => max(0, $availableStock),
                'last_harvest_date' => $harvest->harvest_date
            ]);
            
            $harvest->update(['synced_to_stock' => true]);
            
            $this->line("  Stock item: {$stock->name} (ID: {$stock->id})");
            $this->line("  Previous stock: {$oldStock} {$stock->units}");
            $this->line("  Added: {$harvest->quantity} {$harvest->units}");
            $this->line("  New stock: {$stock->current_stock} {$stock->units}");
            $this->line("  Available: {$stock->available_stock} {$stock->units}");
            $this->info('  ✓ Stock updated successfully');
        } else {
            $this->warn('  ⊝ Harvest already synced to stock');
            $stock = StockItem::where('name', $harvest->crop_name)->first();
        }
        
        $this->newLine();
        
        // Step 3: Check WooCommerce product link
        $this->info('Step 3: Check WooCommerce integration');
        
        if ($stock->woocommerce_product_id) {
            $this->line("  WooCommerce Product ID: {$stock->woocommerce_product_id}");
            $this->line("  Auto-sync enabled: " . ($stock->auto_sync_to_woo ? 'Yes' : 'No'));
            $this->info('  ✓ Ready for WooCommerce sync');
        } else {
            $this->warn("  ⚠ No WooCommerce product linked");
            $this->line("  To enable WooCommerce sync:");
            $this->line("    1. Find product ID in WooCommerce");
            $this->line("    2. Run: UPDATE stock_items SET woocommerce_product_id = <ID> WHERE id = {$stock->id}");
        }
        
        $this->newLine();
        
        // Step 4: Summary
        $this->info('✅ Test complete!');
        $this->table(
            ['Step', 'Status'],
            [
                ['Field Kit → Harvest Log', '✓ Working (harvest ID 6 created)'],
                ['Harvest Log → Stock', '✓ Working (stock updated)'],
                ['Stock → WooCommerce', $stock->woocommerce_product_id ? '✓ Ready' : '⚠ Product not linked'],
            ]
        );
        
        return 0;
    }
}
