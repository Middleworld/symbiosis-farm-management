<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\WeatherDataIngestionService;
use Carbon\Carbon;

class IngestHistoricalWeatherData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'weather:ingest-historical
                            {--start-date= : Start date (YYYY-MM-DD, default: 45 years ago)}
                            {--end-date= : End date (YYYY-MM-DD, default: yesterday)}
                            {--batch-size=30 : Days per batch}
                            {--dry-run : Show what would be done without actually doing it}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ingest historical weather data for RAG AI system (45+ years)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $startDate = $this->option('start-date') ?: Carbon::now()->subYears(45)->format('Y-m-d');
        $endDate = $this->option('end-date') ?: Carbon::yesterday()->format('Y-m-d');
        $batchSize = (int) $this->option('batch-size');
        $dryRun = $this->option('dry-run');

        $this->info("🌤️ Weather Data Ingestion for RAG AI System");
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("Period: {$startDate} to {$endDate}");
        $this->info("Batch Size: {$batchSize} days");
        $this->info("Mode: " . ($dryRun ? "DRY RUN (no data will be saved)" : "LIVE INGESTION"));

        // Calculate total days
        $totalDays = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;
        $estimatedBatches = ceil($totalDays / $batchSize);

        $this->info("Total Days: {$totalDays}");
        $this->info("Estimated Batches: {$estimatedBatches}");
        $this->newLine();

        if (!$this->confirm('Do you want to proceed with the ingestion?', true)) {
            $this->info('Ingestion cancelled.');
            return;
        }

        if ($dryRun) {
            $this->warn("DRY RUN MODE - No data will be saved to database");
            $this->newLine();
        }

        $progressBar = $this->output->createProgressBar($estimatedBatches);
        $progressBar->start();

        $ingestionService = app(WeatherDataIngestionService::class);

        try {
            if (!$dryRun) {
                $result = $ingestionService->ingestHistoricalData($startDate, $endDate, $batchSize);

                $progressBar->finish();
                $this->newLine(2);

                $this->info("✅ Ingestion Complete!");
                $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
                $this->info("Total Days Processed: {$result['processed']}");
                $this->info("Data Points Created: {$result['data_points']}");
                $this->info("Errors: {$result['errors']}");
                $this->info("Success Rate: " . round(($result['processed'] / $totalDays) * 100, 1) . "%");

                if ($result['data_points'] > 0) {
                    $this->newLine();
                    $this->info("🎯 Next Steps:");
                    $this->info("1. Run: php artisan ai:ingest weather_historical_rag");
                    $this->info("2. Your AI can now query 45+ years of weather data!");
                    $this->info("3. Example queries:");
                    $this->info("   - 'What were the frost patterns in March over the last 10 years?'");
                    $this->info("   - 'When is the best time to plant carrots based on historical data?'");
                    $this->info("   - 'How have growing degree days changed over the decades?'");
                }
            } else {
                $progressBar->finish();
                $this->newLine(2);
                $this->info("🔍 Dry Run Complete!");
                $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
                $this->info("Would have processed: {$totalDays} days");
                $this->info("Would have created: ~" . ($totalDays * 1.2) . " data points (estimated)");
                $this->info("Estimated API calls: {$estimatedBatches}");
                $this->newLine();
                $this->info("💡 To run actual ingestion:");
                $this->info("   php artisan weather:ingest-historical --start-date={$startDate} --end-date={$endDate}");
            }

        } catch (\Exception $e) {
            $progressBar->finish();
            $this->newLine();
            $this->error("❌ Ingestion failed: " . $e->getMessage());
            $this->error("Check your OPENWEATHER_API_KEY and MET_OFFICE_API_KEY in .env file");
            return 1;
        }

        return 0;
    }
}
