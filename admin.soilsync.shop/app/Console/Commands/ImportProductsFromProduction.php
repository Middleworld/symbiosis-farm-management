<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use Illuminate\Support\Str;

class ImportProductsFromProduction extends Command
{
    protected $signature = 'products:import-from-production 
                            {--limit= : Limit number of products to import}
                            {--dry-run : Preview import without making changes}';

    protected $description = 'Import products from production WordPress database (wp_pxmxy) to demo Laravel';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $limit = $this->option('limit');
        
        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No data will be imported');
        }
        
        $this->info('📦 Importing products from production WordPress...');
        $this->newLine();
        
        // Connect to production WordPress database
        $prodDb = 'mysql'; // Will use default connection but query different database
        
        // Get published products from production
        $query = DB::connection($prodDb)
            ->table('wp_pxmxy.D6sPMX_posts')
            ->select('ID', 'post_title', 'post_content', 'post_status', 'post_date')
            ->where('post_type', 'product')
            ->where('post_status', 'publish')
            ->orderBy('post_title');
            
        if ($limit) {
            $query->limit((int)$limit);
        }
        
        $products = $query->get();
        
        $this->info("Found {$products->count()} products in production");
        $this->newLine();
        
        $imported = 0;
        $skipped = 0;
        $errors = 0;
        
        foreach ($products as $wpProduct) {
            try {
                // Get product meta
                $meta = $this->getProductMeta($wpProduct->ID);
                $imageUrl = $this->getProductImage($wpProduct->ID);
                
                // Skip if already imported (check by woo_product_id)
                $existing = Product::where('woo_product_id', $wpProduct->ID)->first();
                if ($existing) {
                    $skipped++;
                    $this->line("⏭️  Skipped: {$wpProduct->post_title} (already imported)");
                    continue;
                }
                
                // Handle stock quantity - WooCommerce uses empty string or '?' for unmanaged stock
                $stockQty = 0;
                if (!empty($meta['_stock']) && is_numeric($meta['_stock'])) {
                    $stockQty = intval($meta['_stock']);
                }
                
                $productData = [
                    'name' => $wpProduct->post_title,
                    'description' => $wpProduct->post_content,
                    'sku' => $meta['_sku'] ?? Str::slug($wpProduct->post_title),
                    'price' => floatval($meta['_regular_price'] ?? 0),
                    'sale_price' => !empty($meta['_sale_price']) ? floatval($meta['_sale_price']) : null,
                    'stock_quantity' => $stockQty,
                    'product_type' => $meta['_product_type'] ?? 'simple',
                    'woo_product_id' => $wpProduct->ID,
                    'is_active' => true,
                    'manage_stock' => ($meta['_manage_stock'] ?? 'no') === 'yes',
                    'category' => $this->getProductCategory($wpProduct->ID),
                    'image_url' => $imageUrl,
                ];
                
                if ($dryRun) {
                    $this->info("✅ Would import: {$wpProduct->post_title}");
                    $this->line("   Price: £{$productData['price']}" . 
                               ($productData['sale_price'] ? " (Sale: £{$productData['sale_price']})" : ''));
                    $this->line("   SKU: {$productData['sku']}");
                    $this->line("   Stock: " . ($productData['stock_quantity'] ?? 'Not managed'));
                    if ($productData['category']) {
                        $this->line("   Category: {$productData['category']}");
                    }
                    if ($imageUrl) {
                        $this->line("   Image: " . basename($imageUrl));
                    }
                    $imported++;
                } else {
                    $product = Product::create($productData);
                    $this->info("✅ Imported: {$product->name} (ID: {$product->id})");
                    $imported++;
                }
                
            } catch (\Exception $e) {
                $errors++;
                $this->error("❌ Error importing {$wpProduct->post_title}: {$e->getMessage()}");
            }
        }
        
        $this->newLine();
        $this->info('📊 Import Summary:');
        $this->table(
            ['Status', 'Count'],
            [
                ['Imported', $imported],
                ['Skipped (already exists)', $skipped],
                ['Errors', $errors],
            ]
        );
        
        if ($dryRun) {
            $this->newLine();
            $this->warn('This was a dry run. Run without --dry-run to actually import products.');
        }
        
        return 0;
    }
    
    private function getProductMeta($productId): array
    {
        $metaRows = DB::connection('mysql')
            ->table('wp_pxmxy.D6sPMX_postmeta')
            ->where('post_id', $productId)
            ->whereIn('meta_key', [
                '_sku',
                '_regular_price',
                '_sale_price',
                '_price',
                '_stock',
                '_manage_stock',
                '_stock_status',
                '_product_type',
            ])
            ->get();
        
        $meta = [];
        foreach ($metaRows as $row) {
            $meta[$row->meta_key] = $row->meta_value;
        }
        
        return $meta;
    }
    
    private function getProductCategory($productId): ?string
    {
        // Get primary category
        $category = DB::connection('mysql')
            ->table('wp_pxmxy.D6sPMX_term_relationships as tr')
            ->join('wp_pxmxy.D6sPMX_term_taxonomy as tt', 'tr.term_taxonomy_id', '=', 'tt.term_taxonomy_id')
            ->join('wp_pxmxy.D6sPMX_terms as t', 'tt.term_id', '=', 't.term_id')
            ->where('tr.object_id', $productId)
            ->where('tt.taxonomy', 'product_cat')
            ->orderBy('tr.term_order')
            ->value('t.name');
        
        return $category;
    }
    
    private function getProductImage($productId): ?string
    {
        // Get featured image (thumbnail) ID
        $thumbnailId = DB::connection('mysql')
            ->table('wp_pxmxy.D6sPMX_postmeta')
            ->where('post_id', $productId)
            ->where('meta_key', '_thumbnail_id')
            ->value('meta_value');
        
        if (!$thumbnailId) {
            return null;
        }
        
        // Get image URL from attachment post
        $imageUrl = DB::connection('mysql')
            ->table('wp_pxmxy.D6sPMX_posts')
            ->where('ID', $thumbnailId)
            ->where('post_type', 'attachment')
            ->value('guid');
        
        return $imageUrl;
    }
}
