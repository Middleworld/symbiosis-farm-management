<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\WooCommerceApiService;
use Illuminate\Console\Command;

class SyncProductsToWooCommerce extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:sync-to-woocommerce 
                            {--product= : Sync specific product by ID}
                            {--force : Force sync even if already synced}
                            {--dry-run : Preview sync without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Laravel products (including variations and solidarity pricing) to WooCommerce';

    protected $wooService;

    public function __construct(WooCommerceApiService $wooService)
    {
        parent::__construct();
        $this->wooService = $wooService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting WooCommerce product sync...');
        $this->newLine();

        // Get products to sync
        $query = Product::where('is_active', true);

        if ($productId = $this->option('product')) {
            $query->where('id', $productId);
        }

        if (!$this->option('force')) {
            // Only sync products without WooCommerce ID (never synced before)
            $query->whereNull('woo_product_id');
        }

        $products = $query->get();

        if ($products->isEmpty()) {
            $this->warn('No products found to sync.');
            return 0;
        }

        $this->info("Found {$products->count()} product(s) to sync.");
        $this->newLine();

        $successful = 0;
        $failed = 0;
        $errors = [];

        foreach ($products as $product) {
            $this->line("Processing: {$product->name} (ID: {$product->id})");

            if ($this->option('dry-run')) {
                $this->info("  [DRY RUN] Would sync product:");
                $this->line("    - Type: {$product->product_type}");
                $this->line("    - SKU: {$product->sku}");
                $this->line("    - Price: £{$product->price}");
                
                if ($product->product_type === 'variable') {
                    $this->line("    - Variations: {$product->variations->count()}");
                }
                
                $metadata = $product->metadata ?? [];
                if (isset($metadata['solidarity_pricing_enabled']) && $metadata['solidarity_pricing_enabled']) {
                    $this->line("    - Solidarity Pricing: Enabled");
                }
                
                $successful++;
                continue;
            }

            try {
                $result = $this->wooService->syncProduct($product);

                if ($result['success']) {
                    $this->info("  ✓ Synced successfully");
                    
                    if ($product->woo_product_id) {
                        $this->line("    WooCommerce ID: {$product->woo_product_id}");
                    }
                    
                    if ($product->product_type === 'variable') {
                        $syncedVariations = $product->variations->whereNotNull('woo_variation_id')->count();
                        $this->line("    Variations synced: {$syncedVariations}/{$product->variations->count()}");
                    }
                    
                    $successful++;
                } else {
                    $this->error("  ✗ Sync failed: {$result['message']}");
                    $failed++;
                    $errors[] = [
                        'product' => $product->name,
                        'error' => $result['message']
                    ];
                }
            } catch (\Exception $e) {
                $this->error("  ✗ Exception: {$e->getMessage()}");
                $failed++;
                $errors[] = [
                    'product' => $product->name,
                    'error' => $e->getMessage()
                ];
            }

            $this->newLine();
        }

        // Summary
        $this->newLine();
        $this->info('═══════════════════════════════════════');
        $this->info('Sync Summary:');
        $this->info('═══════════════════════════════════════');
        $this->line("Total products: {$products->count()}");
        $this->info("✓ Successful: {$successful}");
        
        if ($failed > 0) {
            $this->error("✗ Failed: {$failed}");
            
            if (!empty($errors)) {
                $this->newLine();
                $this->error('Failed Products:');
                foreach ($errors as $error) {
                    $this->line("  - {$error['product']}: {$error['error']}");
                }
            }
        }

        return $failed > 0 ? 1 : 0;
    }
}
