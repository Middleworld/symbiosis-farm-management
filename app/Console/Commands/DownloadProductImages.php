<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use App\Models\Product;

class DownloadProductImages extends Command
{
    protected $signature = 'products:download-images 
                            {--dry-run : Preview without downloading}
                            {--force : Re-download existing images}';

    protected $description = 'Download product images from production URLs and store locally';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');
        
        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No images will be downloaded');
        }
        
        $this->info('📥 Downloading product images from production...');
        $this->newLine();
        
        // Get products with external image URLs
        $query = Product::whereNotNull('image_url')
            ->where('image_url', 'like', 'http%');
        
        if (!$force) {
            // Only download if local file doesn't exist
            $query->where(function($q) {
                $q->whereNull('local_image_path')
                  ->orWhere('local_image_path', '');
            });
        }
        
        $products = $query->get();
        
        $this->info("Found {$products->count()} products with external images");
        $this->newLine();
        
        $downloaded = 0;
        $skipped = 0;
        $errors = 0;
        
        // Create products/images directory if it doesn't exist
        $imageDir = 'products/images';
        if (!Storage::disk('public')->exists($imageDir)) {
            Storage::disk('public')->makeDirectory($imageDir);
        }
        
        foreach ($products as $product) {
            try {
                $imageUrl = $product->image_url;
                
                // Extract filename from URL
                $filename = basename(parse_url($imageUrl, PHP_URL_PATH));
                $localPath = "{$imageDir}/{$filename}";
                
                // Check if file already exists and not forcing
                if (!$force && Storage::disk('public')->exists($localPath)) {
                    $skipped++;
                    $this->line("⏭️  {$product->name} - already downloaded");
                    continue;
                }
                
                if ($dryRun) {
                    $this->info("✅ Would download: {$product->name}");
                    $this->line("   From: {$imageUrl}");
                    $this->line("   To: {$localPath}");
                    $downloaded++;
                } else {
                    // Download the image
                    $this->line("⬇️  Downloading {$filename}...");
                    
                    $response = Http::timeout(30)->get($imageUrl);
                    
                    if ($response->successful()) {
                        // Save the image
                        Storage::disk('public')->put($localPath, $response->body());
                        
                        // Update product with local path
                        $product->update([
                            'local_image_path' => $localPath
                        ]);
                        
                        $this->info("✅ {$product->name} → {$filename}");
                        $downloaded++;
                    } else {
                        $this->error("❌ Failed to download {$product->name}: HTTP {$response->status()}");
                        $errors++;
                    }
                }
                
            } catch (\Exception $e) {
                $errors++;
                $this->error("❌ Error downloading {$product->name}: {$e->getMessage()}");
            }
        }
        
        $this->newLine();
        $this->info('📊 Download Summary:');
        $this->table(
            ['Status', 'Count'],
            [
                ['Downloaded', $downloaded],
                ['Skipped (already exists)', $skipped],
                ['Errors', $errors],
            ]
        );
        
        if ($dryRun) {
            $this->newLine();
            $this->warn('This was a dry run. Run without --dry-run to download images.');
        } else {
            $this->newLine();
            $this->info("Images saved to: storage/app/{$imageDir}");
            $this->info("Public URL: /storage/products/images/[filename]");
        }
        
        return 0;
    }
}
