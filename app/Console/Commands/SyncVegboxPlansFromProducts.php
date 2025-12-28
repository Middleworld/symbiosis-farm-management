<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\VegboxPlan;
use App\Models\Product;
use Illuminate\Support\Str;

class SyncVegboxPlansFromProducts extends Command
{
    protected $signature = 'vegbox:sync-plans-from-products';
    
    protected $description = 'Create VegboxPlan records from existing vegbox subscription products';

    public function handle()
    {
        $this->info('🔄 Syncing vegbox plans from products...');
        $this->newLine();
        
        // Vegbox subscription product IDs
        $vegboxProductIds = [226084, 226083, 226081, 226082];
        
        $products = Product::whereIn('woo_product_id', $vegboxProductIds)->get();
        
        if ($products->isEmpty()) {
            $this->error('No vegbox products found!');
            return 1;
        }
        
        $this->info("Found {$products->count()} vegbox products");
        $this->newLine();
        
        // Define token allocations based on box size
        $tokenMap = [
            'single' => 8,    // Single Person Box
            'couple' => 10,   // Couple's Box
            'small' => 12,    // Small Family Box
            'large' => 16,    // Large Family Box
        ];
        
        $created = 0;
        $updated = 0;
        
        foreach ($products as $product) {
            // Determine box size from product name
            $boxSize = $this->determineBoxSize($product->name);
            $defaultTokens = $tokenMap[$boxSize] ?? 10;
            
            // Check if plan already exists
            $plan = VegboxPlan::where('slug', $product->sku)->first();
            
            $planData = [
                'name' => $product->name,
                'slug' => $product->sku,
                'description' => $product->description ?? "Weekly vegetable box subscription",
                'box_size' => $boxSize,
                'delivery_frequency' => 'weekly',
                'default_tokens' => $defaultTokens,
                'price' => $product->price ?? 0,
                'currency' => 'GBP',
                'invoice_period' => 1,
                'invoice_interval' => 'week',
                'is_active' => true,
                'sort_order' => $this->getSortOrder($boxSize),
            ];
            
            if ($plan) {
                $plan->update($planData);
                $this->info("✅ Updated: {$product->name} ({$defaultTokens} tokens)");
                $updated++;
            } else {
                VegboxPlan::create($planData);
                $this->info("✅ Created: {$product->name} ({$defaultTokens} tokens)");
                $created++;
            }
        }
        
        $this->newLine();
        $this->info('📊 Summary:');
        $this->table(
            ['Action', 'Count'],
            [
                ['Created', $created],
                ['Updated', $updated],
            ]
        );
        
        return 0;
    }
    
    private function determineBoxSize($name)
    {
        $name = strtolower($name);
        
        if (str_contains($name, 'single person')) {
            return 'single';
        } elseif (str_contains($name, 'couple')) {
            return 'couple';
        } elseif (str_contains($name, 'small family')) {
            return 'small';
        } elseif (str_contains($name, 'large family')) {
            return 'large';
        }
        
        return 'medium'; // default
    }
    
    private function getSortOrder($boxSize)
    {
        return match($boxSize) {
            'single' => 1,
            'couple' => 2,
            'small' => 3,
            'large' => 4,
            default => 5,
        };
    }
}
