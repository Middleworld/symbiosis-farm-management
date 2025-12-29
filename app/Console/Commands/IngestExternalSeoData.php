<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class IngestExternalSeoData extends Command
{
    protected $signature = 'seo:ingest-external 
                            {--source=all : Which source to ingest (rhs, organic, seasonal, all)}';

    protected $description = 'Download and ingest external agricultural SEO data from trusted sources';

    protected $sources = [
        'rhs' => [
            'name' => 'RHS Growing Guides',
            'urls' => [
                'https://www.rhs.org.uk/vegetables',
                'https://www.rhs.org.uk/advice/grow-your-own',
            ],
            'type' => 'growing_guides'
        ],
        'organic' => [
            'name' => 'Organic Gardening Data',
            'urls' => [
                'https://www.gardenorganic.org.uk/expert-advice',
            ],
            'type' => 'organic_methods'
        ],
        'seasonal' => [
            'name' => 'UK Seasonal Calendar',
            'data' => 'embedded', // Use embedded data
            'type' => 'seasonal_calendar'
        ]
    ];

    public function handle()
    {
        $this->info('📥 Ingesting external SEO data sources...');
        $this->newLine();

        $source = $this->option('source');

        if ($source === 'all') {
            foreach (array_keys($this->sources) as $sourceName) {
                $this->ingestSource($sourceName);
            }
        } else {
            if (!isset($this->sources[$source])) {
                $this->error("Unknown source: {$source}");
                $this->line("Available sources: " . implode(', ', array_keys($this->sources)));
                return 1;
            }
            
            $this->ingestSource($source);
        }

        $this->newLine();
        $this->info('✅ External data ingestion complete!');
        return 0;
    }

    protected function ingestSource(string $sourceName)
    {
        $config = $this->sources[$sourceName];
        $this->info("📦 Processing: {$config['name']}");

        if ($config['data'] === 'embedded') {
            $this->ingestEmbeddedData($sourceName);
            return;
        }

        // For now, we'll create structured SEO data files
        // In production, you'd scrape these sites (with permission) or use their APIs
        $this->createStructuredData($sourceName);
    }

    protected function ingestEmbeddedData(string $source)
    {
        if ($source === 'seasonal') {
            $this->ingestSeasonalCalendar();
        }
    }

    protected function ingestSeasonalCalendar()
    {
        $calendar = [
            'january' => ['winter_vegetables' => 'brussels sprouts, cabbages, cauliflowers, celeriac, chard, chicory, jerusalem artichokes, kale, leeks, parsnips, swedes, turnips'],
            'february' => ['winter_vegetables' => 'brussels sprouts, cabbages, cauliflowers, celeriac, chicory, kale, leeks, parsnips, purple sprouting broccoli, swedes'],
            'march' => ['spring_vegetables' => 'cabbages, cauliflowers, chicory, kale, leeks, purple sprouting broccoli, spring greens, spring onions'],
            'april' => ['spring_vegetables' => 'asparagus, purple sprouting broccoli, radishes, rocket, spring greens, spring onions, watercress'],
            'may' => ['late_spring' => 'asparagus, broad beans, new potatoes, peas, radishes, rocket, salad leaves, spring onions'],
            'june' => ['early_summer' => 'asparagus, broad beans, carrots, courgettes, new potatoes, peas, radishes, runner beans, salad leaves'],
            'july' => ['summer' => 'beetroot, broad beans, carrots, courgettes, cucumbers, french beans, new potatoes, peas, radishes, runner beans, salad leaves, tomatoes'],
            'august' => ['late_summer' => 'beetroot, carrots, courgettes, cucumbers, french beans, peppers, potatoes, runner beans, sweetcorn, tomatoes'],
            'september' => ['early_autumn' => 'beetroot, cabbages, carrots, courgettes, cucumbers, french beans, leeks, peppers, potatoes, pumpkins, runner beans, sweetcorn, tomatoes'],
            'october' => ['autumn' => 'beetroot, cabbages, carrots, celeriac, chard, kale, leeks, parsnips, potatoes, pumpkins, squashes, swedes, turnips'],
            'november' => ['late_autumn' => 'beetroot, brussels sprouts, cabbages, carrots, celeriac, chard, kale, leeks, parsnips, swedes, turnips'],
            'december' => ['winter' => 'brussels sprouts, cabbages, carrots, celeriac, chard, kale, leeks, parsnips, swedes, turnips']
        ];

        $ragDocuments = [];
        
        foreach ($calendar as $month => $data) {
            foreach ($data as $season => $vegetables) {
                $ragDocuments[] = [
                    'id' => "seasonal_{$month}_{$season}",
                    'text' => "In {$month}, UK seasonal vegetables include: {$vegetables}. Season: {$season}.",
                    'metadata' => [
                        'type' => 'seasonal_calendar',
                        'month' => $month,
                        'season' => $season,
                        'source' => 'UK Growing Calendar'
                    ]
                ];
            }
        }

        // Save to storage for RAG ingestion
        $filename = 'rag/external/seasonal_calendar.json';
        Storage::put($filename, json_encode($ragDocuments, JSON_PRETTY_PRINT));
        
        $this->info("  ✓ Created seasonal calendar data: " . count($ragDocuments) . " entries");
        $this->line("  📁 Saved to: storage/app/{$filename}");
    }

    protected function createStructuredData(string $source)
    {
        $config = $this->sources[$source];
        
        // Create template data structures for manual population
        // In production, these would be scraped or fetched from APIs
        
        $data = [];
        
        if ($source === 'rhs') {
            $data = $this->createRHSData();
        } elseif ($source === 'organic') {
            $data = $this->createOrganicData();
        }

        $filename = "rag/external/{$source}_data.json";
        Storage::put($filename, json_encode($data, JSON_PRETTY_PRINT));
        
        $this->info("  ✓ Created {$config['name']} template: " . count($data) . " entries");
        $this->line("  📁 Saved to: storage/app/{$filename}");
        $this->line("  ℹ️  Populate this file with scraped/API data for full RAG ingestion");
    }

    protected function createRHSData(): array
    {
        // Template RHS growing guide data
        return [
            [
                'id' => 'rhs_tomatoes',
                'crop' => 'Tomatoes',
                'text' => 'Tomatoes are tender plants that need warmth to grow well. In the UK, grow them in a greenhouse or warm, sheltered spot outdoors. Start seeds indoors 6-8 weeks before last frost. Plant out after all frost risk has passed. Water regularly and feed weekly once fruits start forming. Popular UK varieties include Gardeners Delight, Sungold, and Alicante.',
                'metadata' => ['type' => 'rhs_guide', 'crop_type' => 'tender', 'growing_season' => 'summer']
            ],
            [
                'id' => 'rhs_potatoes',
                'crop' => 'Potatoes',
                'text' => 'Potatoes are easy to grow in the UK. Plant seed potatoes 10-15cm deep in March-May depending on variety. Earth up stems as they grow. First earlies mature in 10-12 weeks, second earlies in 13-15 weeks, maincrop in 15-20 weeks. Harvest when flowers fade. Good UK varieties include Charlotte, Maris Piper, and Desiree.',
                'metadata' => ['type' => 'rhs_guide', 'crop_type' => 'easy', 'growing_season' => 'spring-autumn']
            ],
            [
                'id' => 'rhs_template',
                'crop' => 'TEMPLATE',
                'text' => 'Add more RHS growing guide data here...',
                'metadata' => ['type' => 'rhs_guide', 'source' => 'https://www.rhs.org.uk']
            ]
        ];
    }

    protected function createOrganicData(): array
    {
        // Template organic gardening data
        return [
            [
                'id' => 'organic_composting',
                'topic' => 'Composting',
                'text' => 'Good compost is the foundation of organic gardening. Mix green materials (grass clippings, vegetable waste) with brown materials (cardboard, dry leaves) in roughly equal proportions. Keep moist but not waterlogged. Turn regularly. Compost should be ready in 6-12 months.',
                'metadata' => ['type' => 'organic_method', 'category' => 'soil_health']
            ],
            [
                'id' => 'organic_pest_control',
                'topic' => 'Natural Pest Control',
                'text' => 'Encourage natural predators like ladybirds, lacewings, and birds. Use companion planting to deter pests. Physical barriers like netting protect crops. Homemade sprays from garlic, neem oil, or soap can control aphids and other pests organically.',
                'metadata' => ['type' => 'organic_method', 'category' => 'pest_management']
            ],
            [
                'id' => 'organic_template',
                'topic' => 'TEMPLATE',
                'text' => 'Add more organic gardening methods here...',
                'metadata' => ['type' => 'organic_method', 'source' => 'https://www.gardenorganic.org.uk']
            ]
        ];
    }
}
