<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Product;

class UpdateProductImages extends Command
{
    protected $signature = 'products:update-images 
                            {--dry-run : Preview without making changes}';

    protected $description = 'Update product images from production WordPress database';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No data will be updated');
        }
        
        $this->info('🖼️  Updating product images from production...');
        $this->newLine();
        
        $products = Product::whereNotNull('woo_product_id')
            ->whereNull('image_url')
            ->get();
        
        $this->info("Found {$products->count()} products without images");
        $this->newLine();
        
        $updated = 0;
        $noImage = 0;
        
        foreach ($products as $product) {
            $imageUrl = $this->getProductImage($product->woo_product_id);
            
            if ($imageUrl) {
                if ($dryRun) {
                    $this->info("✅ {$product->name}");
                    $this->line("   Would set image: " . basename($imageUrl));
                } else {
                    $product->update(['image_url' => $imageUrl]);
                    $this->info("✅ {$product->name} → " . basename($imageUrl));
                }
                $updated++;
            } else {
                $this->line("⚠️  {$product->name} - no image in production");
                $noImage++;
            }
        }
        
        $this->newLine();
        $this->info('📊 Update Summary:');
        $this->table(
            ['Status', 'Count'],
            [
                ['Updated', $updated],
                ['No image available', $noImage],
            ]
        );
        
        if ($dryRun) {
            $this->newLine();
            $this->warn('This was a dry run. Run without --dry-run to update images.');
        }
        
        return 0;
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
