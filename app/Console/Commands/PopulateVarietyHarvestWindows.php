<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PlantVariety;
use App\Services\AI\SymbiosisAIService;
use Illuminate\Support\Facades\Log;

class PopulateVarietyHarvestWindows extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'varieties:populate-harvest-windows {--limit= : Limit number of varieties to process} {--force : Force update even if data exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populate harvest window data for plant varieties using AI analysis';

    protected $symbiosisAI;

    public function __construct(SymbiosisAIService $symbiosisAI)
    {
        parent::__construct();
        $this->symbiosisAI = $symbiosisAI;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $limit = $this->option('limit');
        $force = $this->option('force');

        $query = PlantVariety::active();

        if (!$force) {
            $query->whereNull('harvest_start');
        }

        if ($limit) {
            $query->limit($limit);
        }

        $varieties = $query->get();

        if ($varieties->isEmpty()) {
            $this->info('No varieties found that need harvest window data.');
            return;
        }

        $this->info("Processing {$varieties->count()} varieties...");

        $progressBar = $this->output->createProgressBar($varieties->count());
        $progressBar->start();

        $successCount = 0;
        $errorCount = 0;

        foreach ($varieties as $variety) {
            try {
                $harvestData = $this->getHarvestWindowData($variety);

                if ($harvestData) {
                    $variety->update($harvestData);
                    $successCount++;
                } else {
                    $this->warn("No harvest data generated for {$variety->name}");
                    $errorCount++;
                }

            } catch (\Exception $e) {
                $this->error("Failed to process variety {$variety->name}: {$e->getMessage()}");
                $this->error("Stack trace: {$e->getTraceAsString()}");
                $errorCount++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("✅ Completed: {$successCount} varieties updated, {$errorCount} errors");
    }

    private function getHarvestWindowData(PlantVariety $variety): ?array
    {
        // For now, skip AI and use intelligent fallback data
        // TODO: Re-enable AI parsing once response format is improved
        $this->info("Generating harvest data for {$variety->name} using fallback logic");
        
        return $this->getFallbackData($variety);
        
        /* Original AI code - commented out for now
        try {
            $prompt = $this->buildAIPrompt($variety);
            $aiResponse = $this->symbiosisAI->chat([$prompt]);

            Log::info('AI harvest window response for ' . $variety->name, ['response' => $aiResponse]);

            return $this->parseAIResponse($aiResponse);

        } catch (\Exception $e) {
            Log::warning('AI harvest window failed for ' . $variety->name, ['error' => $e->getMessage()]);
            return $this->getFallbackData($variety);
        }
        */
    }

    private function buildAIPrompt(PlantVariety $variety): string
    {
        $prompt = "Analyze the harvest window for {$variety->name}";

        if ($variety->plant_type) {
            $prompt .= " ({$variety->plant_type})";
        }

        if ($variety->maturity_days) {
            $prompt .= ". This variety takes {$variety->maturity_days} days to mature";
        }

        if ($variety->season) {
            $prompt .= ". It's a {$variety->season} season variety";
        }

        $prompt .= ". Provide optimal harvest window information in JSON format with these keys:
        - harvest_start: optimal harvest start date (MM-DD format, no year)
        - harvest_end: optimal harvest end date (MM-DD format, no year)
        - yield_peak: peak yield date (MM-DD format, no year)
        - harvest_window_days: duration of harvest window in days
        - harvest_method: harvest method (continuous, once-over, multiple-passes)
        - expected_yield_per_plant: typical yield per plant (numeric)
        - yield_unit: unit of yield (pounds, ounces, bunches, heads, etc.)
        - harvest_notes: any special harvest instructions

        Return only valid JSON.";

        return $prompt;
    }

    private function parseAIResponse(array $response): ?array
    {
        // Extract the content from the AI service response
        $content = $response['choices'][0]['message']['content'] ?? null;

        if (!$content) {
            return null;
        }

        // Try to extract JSON from the response
        $jsonMatch = preg_match('/\{[\s\S]*\}/', $content, $matches);

        if ($jsonMatch && $matches[0]) {
            $data = json_decode($matches[0], true);

            if ($data && isset($data['harvest_start'])) {
                return [
                    'harvest_start' => $this->normalizeDate($data['harvest_start']),
                    'harvest_end' => $this->normalizeDate($data['harvest_end']),
                    'yield_peak' => $this->normalizeDate($data['yield_peak']),
                    'harvest_window_days' => $data['harvest_window_days'] ?? null,
                    'harvest_method' => $data['harvest_method'] ?? null,
                    'expected_yield_per_plant' => $data['expected_yield_per_plant'] ?? null,
                    'yield_unit' => $data['yield_unit'] ?? null,
                    'harvest_notes' => $data['harvest_notes'] ?? null,
                ];
            }
        }

        return null;
    }

    private function normalizeDate(?string $date): ?string
    {
        if (!$date) return null;

        // Convert various date formats to MM-DD
        if (preg_match('/(\d{1,2})[\/\-](\d{1,2})/', $date, $matches)) {
            return sprintf('%02d-%02d', $matches[1], $matches[2]);
        }

        // If it's already MM-DD format, return as is
        if (preg_match('/^\d{2}-\d{2}$/', $date)) {
            return $date;
        }

        return null;
    }

    private function getFallbackData(PlantVariety $variety): ?array
    {
        // Use existing database data to create intelligent fallbacks
        $maturityDays = $variety->maturity_days ?? 60;
        $season = strtolower($variety->season ?? 'cool');
        
        // Calculate harvest window based on maturity and season
        $windowDays = max(14, min(60, $maturityDays / 4)); // 1/4 of maturity time, min 2 weeks
        
        // Season-based date calculations
        $seasonLower = strtolower($season);
        switch ($seasonLower) {
            case 'cool':
            case 'spring':
                $startMonth = 3; // April
                $endMonth = 5;   // June
                break;
            case 'warm':
            case 'summer':
                $startMonth = 6; // July
                $endMonth = 8;   // September
                break;
            case 'fall':
            case 'autumn':
                $startMonth = 8; // September
                $endMonth = 10;  // November
                break;
            case 'all-season':
            case 'year-round':
                $startMonth = 4; // May
                $endMonth = 9;   // October
                break;
            default:
                $startMonth = 6; // July
                $endMonth = 8;   // September
        }
        
        $currentYear = date('Y');
        $startDate = sprintf('%s-%02d-15', $currentYear, $startMonth); // 15th of start month
        $endDate = sprintf('%s-%02d-15', $currentYear, $endMonth);     // 15th of end month
        $peakDate = sprintf('%s-%02d-01', $currentYear, ($startMonth + $endMonth) / 2); // Middle of window
        
        return [
            'harvest_start' => $startDate,
            'harvest_end' => $endDate,
            'yield_peak' => $peakDate,
            'harvest_window_days' => $windowDays,
            'harvest_method' => $this->inferHarvestMethod($variety->name),
            'expected_yield_per_plant' => $this->estimateYield($variety->name),
            'yield_unit' => $this->inferYieldUnit($variety->name),
            'harvest_notes' => "Estimated harvest window for {$variety->name} based on {$season} season and {$maturityDays} day maturity. Please verify with local conditions.",
        ];
    }
    
    private function inferHarvestMethod(string $name): string
    {
        $name = strtolower($name);
        
        if (str_contains($name, 'lettuce') || str_contains($name, 'spinach') || str_contains($name, 'arugula')) {
            return 'continuous';
        }
        
        if (str_contains($name, 'tomato') || str_contains($name, 'pepper') || str_contains($name, 'eggplant')) {
            return 'multiple-passes';
        }
        
        if (str_contains($name, 'carrot') || str_contains($name, 'beet') || str_contains($name, 'radish')) {
            return 'once-over';
        }
        
        return 'continuous'; // Default
    }
    
    private function estimateYield(string $name): float
    {
        $name = strtolower($name);
        
        if (str_contains($name, 'lettuce') || str_contains($name, 'spinach')) {
            return 1.5; // pounds per plant
        }
        
        if (str_contains($name, 'tomato') || str_contains($name, 'pepper')) {
            return 3.0; // pounds per plant
        }
        
        if (str_contains($name, 'carrot') || str_contains($name, 'beet')) {
            return 0.5; // pounds per plant
        }
        
        return 1.0; // Default estimate
    }
    
    private function inferYieldUnit(string $name): string
    {
        $name = strtolower($name);
        
        if (str_contains($name, 'lettuce') || str_contains($name, 'spinach') || str_contains($name, 'kale')) {
            return 'pounds';
        }
        
        if (str_contains($name, 'carrot') || str_contains($name, 'beet') || str_contains($name, 'radish')) {
            return 'pounds';
        }
        
        if (str_contains($name, 'tomato') || str_contains($name, 'pepper')) {
            return 'pounds';
        }
        
        return 'pounds'; // Default
    }
}
