<?php

namespace App\Console\Commands;

use App\Models\PlantVariety;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateSeoKeywords extends Command
{
    protected $signature = 'products:generate-seo-keywords 
                            {--product= : Specific product ID}
                            {--update : Automatically update products with generated keywords}';

    protected $description = 'Generate SEO keywords for products using farmOS plant varieties and agricultural knowledge';

    protected $seasonalTerms = [
        'spring' => ['early season', 'spring planting', 'spring harvest', 'frost tolerant'],
        'summer' => ['summer crop', 'warm season', 'heat loving', 'summer harvest'],
        'autumn' => ['fall harvest', 'autumn planting', 'overwinter', 'cool season'],
        'winter' => ['winter hardy', 'storage crop', 'cold tolerant', 'protected growing']
    ];

    protected $organicTerms = [
        'organic', 'chemical-free', 'sustainable', 'regenerative', 'biodynamic',
        'no-dig', 'permaculture', 'heirloom', 'heritage variety', 'open-pollinated'
    ];

    protected $localTerms = [
        'local farm', 'locally grown', 'farm fresh', 'direct from farm',
        'UK grown', 'British vegetables', 'local produce', 'farm to table'
    ];

    public function handle()
    {
        $this->info('🔍 Generating SEO keywords from agricultural knowledge base...');
        $this->newLine();

        if ($productId = $this->option('product')) {
            $product = Product::find($productId);
            
            if (!$product) {
                $this->error("Product {$productId} not found!");
                return 1;
            }
            
            $this->processProduct($product);
        } else {
            $products = Product::where('is_active', true)->get();
            $progressBar = $this->output->createProgressBar($products->count());
            
            foreach ($products as $product) {
                $this->processProduct($product, true);
                $progressBar->advance();
            }
            
            $progressBar->finish();
            $this->newLine(2);
        }
        
        $this->info('✅ SEO keyword generation complete!');
        return 0;
    }

    protected function processProduct(Product $product, bool $silent = false)
    {
        if (!$silent) {
            $this->info("Product: {$product->name}");
        }
        
        $keywords = $this->generateKeywords($product);
        
        if (!$silent) {
            $this->line("Generated keywords:");
            $this->line("  " . implode(', ', $keywords));
            $this->newLine();
        }
        
        if ($this->option('update')) {
            $metadata = $product->metadata ?? [];
            $metadata['seo_keywords'] = implode(', ', $keywords);
            $product->metadata = $metadata;
            $product->save();
            
            if (!$silent) {
                $this->info('✅ Keywords saved to product metadata');
            }
        }
    }

    protected function generateKeywords(Product $product): array
    {
        $keywords = [];
        
        // 1. Product name variations
        $productName = strtolower($product->name);
        $keywords[] = $productName;
        
        // Add "organic" prefix
        if (!str_contains($productName, 'organic')) {
            $keywords[] = "organic {$productName}";
        }
        
        // 2. Extract vegetable type and add variety-specific keywords
        $varietyKeywords = $this->getVarietyKeywords($productName);
        $keywords = array_merge($keywords, $varietyKeywords);
        
        // 3. Add category-based keywords
        if ($product->category) {
            $keywords[] = strtolower($product->category);
        }
        
        // 4. Add seasonal keywords based on harvest windows
        $seasonalKeywords = $this->getSeasonalKeywords($productName);
        $keywords = array_merge($keywords, $seasonalKeywords);
        
        // 5. Add companion planting keywords from RAG
        $companionKeywords = $this->getCompanionPlantingKeywords($productName);
        $keywords = array_merge($keywords, $companionKeywords);
        
        // 6. Add local/organic terms
        $keywords = array_merge($keywords, array_slice($this->organicTerms, 0, 2));
        $keywords = array_merge($keywords, array_slice($this->localTerms, 0, 2));
        
        // 7. Add box/delivery keywords if it's a veg box
        if (str_contains($productName, 'box')) {
            $keywords[] = 'vegetable box subscription';
            $keywords[] = 'organic veg box delivery';
            $keywords[] = 'seasonal vegetable box';
        }
        
        // Remove duplicates and limit to 10 most relevant
        $keywords = array_unique($keywords);
        return array_slice($keywords, 0, 10);
    }

    protected function getVarietyKeywords(string $productName): array
    {
        $keywords = [];
        
        // Common vegetable mappings
        $vegetables = [
            'tomato' => ['cherry tomato', 'beefsteak tomato', 'plum tomato', 'heritage tomato'],
            'potato' => ['new potatoes', 'salad potatoes', 'baking potatoes', 'organic potatoes'],
            'carrot' => ['heritage carrots', 'rainbow carrots', 'baby carrots'],
            'lettuce' => ['mixed salad', 'salad leaves', 'lettuce mix'],
            'cabbage' => ['spring cabbage', 'savoy cabbage', 'red cabbage'],
            'courgette' => ['zucchini', 'summer squash', 'courgettes'],
            'bean' => ['runner beans', 'french beans', 'broad beans'],
            'onion' => ['red onion', 'white onion', 'spring onions', 'shallots'],
        ];
        
        foreach ($vegetables as $veg => $terms) {
            if (str_contains($productName, $veg)) {
                $keywords = array_merge($keywords, array_slice($terms, 0, 2));
                break;
            }
        }
        
        // Query farmOS varieties
        try {
            foreach (array_keys($vegetables) as $veg) {
                if (str_contains($productName, $veg)) {
                    $varieties = PlantVariety::where('common_name', 'like', "%{$veg}%")
                        ->limit(3)
                        ->pluck('common_name')
                        ->toArray();
                    
                    if (!empty($varieties)) {
                        $keywords = array_merge($keywords, $varieties);
                    }
                    break;
                }
            }
        } catch (\Exception $e) {
            // Continue without variety data
        }
        
        return $keywords;
    }

    protected function getSeasonalKeywords(string $productName): array
    {
        $keywords = [];
        
        try {
            // Query UK planting calendar from RAG database
            $calendar = DB::connection('pgsql_rag')
                ->table('uk_planting_calendar')
                ->where('crop_name', 'like', "%{$productName}%")
                ->first();
            
            if ($calendar) {
                $season = strtolower($calendar->planting_season ?? '');
                
                foreach ($this->seasonalTerms as $seasonName => $terms) {
                    if (str_contains($season, $seasonName)) {
                        $keywords = array_merge($keywords, array_slice($terms, 0, 2));
                        break;
                    }
                }
                
                // Add harvest season
                if ($calendar->harvest_season) {
                    $keywords[] = strtolower($calendar->harvest_season) . ' harvest';
                }
            }
        } catch (\Exception $e) {
            // RAG database not available, use defaults
            $currentMonth = (int) date('n');
            
            if ($currentMonth >= 3 && $currentMonth <= 5) {
                $keywords[] = 'spring vegetables';
            } elseif ($currentMonth >= 6 && $currentMonth <= 8) {
                $keywords[] = 'summer vegetables';
            } elseif ($currentMonth >= 9 && $currentMonth <= 11) {
                $keywords[] = 'autumn vegetables';
            } else {
                $keywords[] = 'winter vegetables';
            }
        }
        
        return $keywords;
    }

    protected function getCompanionPlantingKeywords(string $productName): array
    {
        $keywords = [];
        
        try {
            // Query companion planting knowledge
            $companions = DB::connection('pgsql_rag')
                ->table('companion_planting_knowledge')
                ->where('primary_crop', 'like', "%{$productName}%")
                ->orWhere('companion_crop', 'like', "%{$productName}%")
                ->limit(3)
                ->get();
            
            foreach ($companions as $companion) {
                if (str_contains(strtolower($companion->primary_crop), $productName)) {
                    $keywords[] = 'grows well with ' . strtolower($companion->companion_crop);
                } else {
                    $keywords[] = 'companion to ' . strtolower($companion->primary_crop);
                }
            }
        } catch (\Exception $e) {
            // RAG database not available
        }
        
        return $keywords;
    }
}
