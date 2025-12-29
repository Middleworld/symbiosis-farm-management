<?php

namespace App\Console\Commands;

use App\Services\ProductRagService;
use App\Models\Product;
use Illuminate\Console\Command;

class IngestProductsToRag extends Command
{
    protected $signature = 'products:ingest-rag 
                            {--product= : Specific product ID to ingest}
                            {--force : Re-ingest all products even if already in RAG}';

    protected $description = 'Ingest product data into RAG knowledge base for enhanced SEO';

    public function handle(ProductRagService $ragService)
    {
        $this->info('🌱 Starting product RAG ingestion...');
        
        if ($productId = $this->option('product')) {
            // Ingest single product
            $product = Product::find($productId);
            
            if (!$product) {
                $this->error("Product {$productId} not found!");
                return 1;
            }
            
            $this->info("Ingesting product: {$product->name} ({$product->sku})");
            
            if ($ragService->ingestProduct($product)) {
                $this->info('✅ Product ingested successfully');
                return 0;
            } else {
                $this->error('❌ Failed to ingest product');
                return 1;
            }
        }
        
        // Bulk ingest
        $this->info('Ingesting all active products...');
        $this->newLine();
        
        $results = $ragService->ingestAllProducts();
        
        $this->newLine();
        $this->info("✅ Successfully ingested: {$results['success']} products");
        
        if ($results['failed'] > 0) {
            $this->warn("⚠️  Failed to ingest: {$results['failed']} products");
            
            if (!empty($results['errors'])) {
                $this->newLine();
                $this->error('Failed products:');
                foreach ($results['errors'] as $error) {
                    $this->line("  - {$error}");
                }
            }
        }
        
        $this->newLine();
        $this->info('🎉 RAG ingestion complete!');
        
        return 0;
    }
}
