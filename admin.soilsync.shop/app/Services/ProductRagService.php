<?php

namespace App\Services;

use App\Models\Product;
use App\Models\PlantVariety;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ProductRagService
{
    protected $ragUrl = 'http://localhost:8007';
    
    /**
     * Ingest product into RAG knowledge base
     */
    public function ingestProduct(Product $product): bool
    {
        try {
            // Build rich product document
            $document = $this->buildProductDocument($product);
            
            // Send to RAG ingestion endpoint
            $response = Http::timeout(30)->post("{$this->ragUrl}/ingest", [
                'id' => "product_{$product->id}",
                'text' => $document['text'],
                'metadata' => $document['metadata']
            ]);
            
            if ($response->successful()) {
                Log::info("Product {$product->id} ingested into RAG", ['sku' => $product->sku]);
                return true;
            }
            
            Log::warning("Failed to ingest product {$product->id} into RAG", [
                'status' => $response->status(),
                'error' => $response->body()
            ]);
            
            return false;
            
        } catch (\Exception $e) {
            Log::error("RAG ingestion error for product {$product->id}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Build enriched product document for RAG
     */
    protected function buildProductDocument(Product $product): array
    {
        $text = "Product: {$product->name}\n";
        $text .= "SKU: {$product->sku}\n";
        $text .= "Category: " . ($product->category ?? 'General') . "\n";
        
        if ($product->subcategory) {
            $text .= "Subcategory: {$product->subcategory}\n";
        }
        
        if ($product->description) {
            $text .= "Description: " . strip_tags($product->description) . "\n";
        }
        
        $text .= "Price: £" . number_format($product->price, 2) . "\n";
        
        // Add variety information if available
        $varietyInfo = $this->getVarietyInformation($product);
        if ($varietyInfo) {
            $text .= "\nVariety Information:\n{$varietyInfo}\n";
        }
        
        // Add seasonal information
        $seasonalInfo = $this->getSeasonalInformation($product);
        if ($seasonalInfo) {
            $text .= "\nSeasonal Availability:\n{$seasonalInfo}\n";
        }
        
        // Add SEO keywords if set
        if (!empty($product->metadata['seo_keywords'])) {
            $text .= "\nKeywords: {$product->metadata['seo_keywords']}\n";
        }
        
        $metadata = [
            'product_id' => $product->id,
            'sku' => $product->sku,
            'category' => $product->category,
            'price' => $product->price,
            'type' => 'product',
            'updated_at' => $product->updated_at->toIso8601String()
        ];
        
        return [
            'text' => $text,
            'metadata' => $metadata
        ];
    }
    
    /**
     * Get variety information from PlantVariety model
     */
    protected function getVarietyInformation(Product $product): ?string
    {
        // Try to match product name to plant varieties
        $productName = strtolower($product->name);
        
        // Extract common vegetable names
        $vegetables = [
            'tomato', 'potato', 'carrot', 'onion', 'lettuce', 'cabbage',
            'broccoli', 'cauliflower', 'pepper', 'cucumber', 'courgette',
            'bean', 'pea', 'spinach', 'kale', 'chard', 'beetroot'
        ];
        
        foreach ($vegetables as $veg) {
            if (str_contains($productName, $veg)) {
                $varieties = PlantVariety::where('common_name', 'like', "%{$veg}%")
                    ->orWhere('scientific_name', 'like', "%{$veg}%")
                    ->limit(5)
                    ->get();
                
                if ($varieties->isNotEmpty()) {
                    $info = "Common varieties include: ";
                    $info .= $varieties->pluck('common_name')->join(', ');
                    
                    // Add growing information if available
                    $firstVariety = $varieties->first();
                    if ($firstVariety->days_to_maturity) {
                        $info .= "\nDays to maturity: {$firstVariety->days_to_maturity}";
                    }
                    if ($firstVariety->harvest_window_start && $firstVariety->harvest_window_end) {
                        $info .= "\nHarvest window: {$firstVariety->harvest_window_start} to {$firstVariety->harvest_window_end}";
                    }
                    
                    return $info;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Get seasonal information from UK planting calendar
     */
    protected function getSeasonalInformation(Product $product): ?string
    {
        // Check if pgsql_rag connection is available
        try {
            $calendar = DB::connection('pgsql_rag')
                ->table('uk_planting_calendar')
                ->where('crop_name', 'like', '%' . strtolower($product->name) . '%')
                ->first();
            
            if ($calendar) {
                $info = "Planting season: {$calendar->planting_season}\n";
                $info .= "Harvest season: {$calendar->harvest_season}\n";
                $info .= "Frost hardy: " . ($calendar->frost_hardy ? 'Yes' : 'No');
                return $info;
            }
        } catch (\Exception $e) {
            // RAG database not available
            Log::debug('RAG database not available for seasonal info: ' . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Bulk ingest all products
     */
    public function ingestAllProducts(): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];
        
        $products = Product::where('is_active', true)->get();
        
        foreach ($products as $product) {
            if ($this->ingestProduct($product)) {
                $results['success']++;
            } else {
                $results['failed']++;
                $results['errors'][] = "Product {$product->id} ({$product->sku})";
            }
            
            // Delay to avoid overwhelming RAG service
            usleep(100000); // 100ms
        }
        
        return $results;
    }
    
    /**
     * Delete product from RAG
     */
    public function deleteProduct(int $productId): bool
    {
        try {
            $response = Http::timeout(10)->delete("{$this->ragUrl}/document/product_{$productId}");
            return $response->successful();
        } catch (\Exception $e) {
            Log::error("Failed to delete product {$productId} from RAG: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Search RAG for product-related information
     */
    public function searchProductInfo(string $query, int $limit = 5): array
    {
        try {
            $response = Http::timeout(10)->post("{$this->ragUrl}/search", [
                'query' => $query,
                'limit' => $limit,
                'filter' => ['type' => 'product']
            ]);
            
            if ($response->successful()) {
                return $response->json()['results'] ?? [];
            }
            
            return [];
            
        } catch (\Exception $e) {
            Log::error("RAG search error: " . $e->getMessage());
            return [];
        }
    }
}
