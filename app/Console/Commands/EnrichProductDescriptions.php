<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\PlantVariety;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class EnrichProductDescriptions extends Command
{
    protected $signature = 'products:enrich-descriptions 
                            {--product= : Specific product ID}
                            {--force : Overwrite existing descriptions}
                            {--ai : Use AI to enhance descriptions}';

    protected $description = 'Enrich product descriptions with crop knowledge, seasonal info, and growing details';

    public function handle()
    {
        $this->info('🌱 Enriching product descriptions with agricultural knowledge...');
        $this->newLine();

        if ($productId = $this->option('product')) {
            $product = Product::find($productId);
            
            if (!$product) {
                $this->error("Product {$productId} not found!");
                return 1;
            }
            
            $this->enrichProduct($product);
        } else {
            $products = Product::where('is_active', true)->get();
            $progressBar = $this->output->createProgressBar($products->count());
            
            foreach ($products as $product) {
                $this->enrichProduct($product, true);
                $progressBar->advance();
            }
            
            $progressBar->finish();
            $this->newLine(2);
        }
        
        $this->info('✅ Product enrichment complete!');
        return 0;
    }

    protected function enrichProduct(Product $product, bool $silent = false)
    {
        // Skip if description exists and not forcing
        if ($product->description && !$this->option('force')) {
            if (!$silent) {
                $this->warn("Skipping {$product->name} - description already exists");
            }
            return;
        }
        
        if (!$silent) {
            $this->info("Enriching: {$product->name}");
        }
        
        $enrichedDescription = $this->buildEnrichedDescription($product);
        
        if ($this->option('ai')) {
            $enrichedDescription = $this->enhanceWithAI($product, $enrichedDescription);
        }
        
        $product->description = $enrichedDescription;
        $product->save();
        
        if (!$silent) {
            $this->line("New description:");
            $this->line($this->truncate($enrichedDescription, 200));
            $this->newLine();
        }
    }

    protected function buildEnrichedDescription(Product $product): string
    {
        $parts = [];
        
        // Base description
        $parts[] = $this->getBaseDescription($product);
        
        // Variety information
        $varietyInfo = $this->getVarietyInfo($product);
        if ($varietyInfo) {
            $parts[] = $varietyInfo;
        }
        
        // Seasonal information
        $seasonalInfo = $this->getSeasonalInfo($product);
        if ($seasonalInfo) {
            $parts[] = $seasonalInfo;
        }
        
        // Growing information
        $growingInfo = $this->getGrowingInfo($product);
        if ($growingInfo) {
            $parts[] = $growingInfo;
        }
        
        // Nutritional/culinary information
        $culinaryInfo = $this->getCulinaryInfo($product);
        if ($culinaryInfo) {
            $parts[] = $culinaryInfo;
        }
        
        return implode("\n\n", $parts);
    }

    protected function getBaseDescription(Product $product): string
    {
        $productName = $product->name;
        
        if (str_contains(strtolower($productName), 'box')) {
            return "Our {$productName} brings you the finest seasonal organic vegetables, freshly harvested from our regenerative farm. Each box contains a carefully curated selection of vegetables at their peak flavor and nutritional value.";
        }
        
        return "Organically grown {$productName}, harvested fresh from our sustainable farm in the UK. Grown using regenerative farming practices without synthetic chemicals or pesticides.";
    }

    protected function getVarietyInfo(Product $product): ?string
    {
        $productName = strtolower($product->name);
        
        // Extract vegetable name
        $vegetables = [
            'tomato', 'potato', 'carrot', 'onion', 'lettuce', 'cabbage',
            'broccoli', 'cauliflower', 'pepper', 'cucumber', 'courgette',
            'bean', 'pea', 'spinach', 'kale', 'chard', 'beetroot', 'squash'
        ];
        
        foreach ($vegetables as $veg) {
            if (str_contains($productName, $veg)) {
                $varieties = PlantVariety::where('common_name', 'like', "%{$veg}%")
                    ->limit(3)
                    ->get();
                
                if ($varieties->isNotEmpty()) {
                    $varietyNames = $varieties->pluck('common_name')->toArray();
                    return "We grow heritage and modern varieties including " . $this->formatList($varietyNames) . ", each selected for exceptional flavor and performance in British growing conditions.";
                }
            }
        }
        
        return null;
    }

    protected function getSeasonalInfo(Product $product): ?string
    {
        try {
            $productName = strtolower($product->name);
            
            $calendar = DB::connection('pgsql_rag')
                ->table('uk_planting_calendar')
                ->where('crop_name', 'like', "%{$productName}%")
                ->first();
            
            if ($calendar) {
                $info = "This is a {$calendar->planting_season} planted crop, typically ready for harvest during {$calendar->harvest_season}.";
                
                if ($calendar->frost_hardy) {
                    $info .= " As a frost-hardy variety, it thrives in cooler British weather.";
                }
                
                return $info;
            }
        } catch (\Exception $e) {
            // RAG database not available
        }
        
        // Default seasonal information based on current month
        $month = (int) date('n');
        if ($month >= 3 && $month <= 5) {
            return "Perfect for spring planting, this crop thrives in the warming soil and longer days of the growing season.";
        } elseif ($month >= 6 && $month <= 8) {
            return "A summer favorite, this crop loves the warmth and produces abundantly during the peak growing season.";
        } elseif ($month >= 9 && $month <= 11) {
            return "An autumn harvest crop, providing fresh produce as the season cools and days shorten.";
        } else {
            return "A winter-hardy crop that provides fresh produce during the colder months when local vegetables are most valuable.";
        }
    }

    protected function getGrowingInfo(Product $product): ?string
    {
        $productName = strtolower($product->name);
        
        try {
            // Get rotation information from RAG
            $rotation = DB::connection('pgsql_rag')
                ->table('crop_rotation_knowledge')
                ->where('primary_crop', 'like', "%{$productName}%")
                ->orWhere('follow_crop', 'like', "%{$productName}%")
                ->first();
            
            if ($rotation) {
                return "We practice careful crop rotation to maintain soil health. This crop is part of our {$rotation->crop_family} rotation plan, ensuring optimal nutrient management and pest control without chemicals.";
            }
        } catch (\Exception $e) {
            // RAG not available
        }
        
        return "Grown using no-dig methods and natural compost to build healthy, living soil that produces nutrient-dense vegetables.";
    }

    protected function getCulinaryInfo(Product $product): ?string
    {
        $productName = strtolower($product->name);
        
        $culinaryInfo = [
            'tomato' => 'Perfect for salads, sauces, roasting, or eating fresh. Store at room temperature for best flavor.',
            'potato' => 'Versatile for boiling, roasting, mashing, or baking. Store in a cool, dark place.',
            'carrot' => 'Delicious raw, roasted, or in soups and stews. Store in the fridge crisper drawer.',
            'lettuce' => 'Use fresh in salads within a few days. Best stored in a damp cloth in the fridge.',
            'cabbage' => 'Perfect for coleslaw, stir-fries, or fermented as sauerkraut. Stores well in a cool place.',
            'courgette' => 'Versatile for grilling, roasting, or spiralizing. Use within a week for best quality.',
            'onion' => 'Essential for countless recipes. Store in a cool, dry place with good air circulation.',
            'box' => 'Each vegetable comes with storage and cooking tips. Perfect for meal planning and reducing food waste.'
        ];
        
        foreach ($culinaryInfo as $key => $info) {
            if (str_contains($productName, $key)) {
                return $info;
            }
        }
        
        return null;
    }

    protected function enhanceWithAI(Product $product, string $baseDescription): string
    {
        try {
            $this->line("  Enhancing with AI...");
            
            $prompt = "You are a marketing copywriter for an organic farm. Improve this product description to be more engaging and SEO-friendly while keeping it under 500 characters. Maintain all factual information.\n\nProduct: {$product->name}\n\nCurrent description:\n{$baseDescription}\n\nEnhanced description (under 500 characters):";
            
            $response = Http::timeout(60)->post('http://localhost:8005/ask-ollama', [
                'question' => $prompt,
                'temperature' => 0.7,
                'max_tokens' => 300
            ]);
            
            if ($response->successful()) {
                $aiDescription = $response->json()['answer'] ?? '';
                
                // Clean up AI response
                $aiDescription = trim($aiDescription);
                $aiDescription = preg_replace('/^(Enhanced description:|Description:)/i', '', $aiDescription);
                $aiDescription = trim($aiDescription);
                
                // Limit length
                if (strlen($aiDescription) > 500) {
                    $aiDescription = substr($aiDescription, 0, 497) . '...';
                }
                
                return $aiDescription ?: $baseDescription;
            }
        } catch (\Exception $e) {
            $this->warn("  AI enhancement failed: " . $e->getMessage());
        }
        
        return $baseDescription;
    }

    protected function formatList(array $items): string
    {
        if (count($items) <= 2) {
            return implode(' and ', $items);
        }
        
        $last = array_pop($items);
        return implode(', ', $items) . ', and ' . $last;
    }

    protected function truncate(string $text, int $length): string
    {
        if (strlen($text) <= $length) {
            return $text;
        }
        
        return substr($text, 0, $length) . '...';
    }
}
