<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FarmOSApi;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ImportFarmOSSeasons extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'farmos:import-seasons {--file= : Path to CSV file} {--dry-run : Show what would be imported without actually importing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import season taxonomy terms from CSV into FarmOS';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = $this->option('file') ?: base_path('farmos_seasons_export.csv');
        $dryRun = $this->option('dry-run');

        if (!file_exists($filePath)) {
            $this->error("CSV file not found: {$filePath}");
            return 1;
        }

        $this->info('🌱 Starting FarmOS seasons import...');

        try {
            $farmOSApi = app(FarmOSApi::class);

            // Check existing seasons
            $this->info('📋 Checking existing seasons in FarmOS...');
            $existingSeasons = $farmOSApi->getSeasons();
            $existingLookup = [];

            foreach ($existingSeasons as $season) {
                $name = $season['attributes']['name'] ?? '';
                if ($name) {
                    $existingLookup[$name] = [
                        'id' => $season['id'],
                        'description' => $season['attributes']['description']['value'] ?? ''
                    ];
                }
            }

            $this->info("📊 Found " . count($existingSeasons) . " existing seasons in FarmOS");
            
            if ($this->option('verbose')) {
                $this->info("Existing seasons:");
                foreach ($existingLookup as $name => $data) {
                    $desc = substr($data['description'], 0, 50) . (strlen($data['description']) > 50 ? '...' : '');
                    $this->line("  - {$name}: {$desc}");
                }
            }

            // Read CSV
            $this->info("📖 Reading CSV file: {$filePath}");
            $csvData = $this->readCsv($filePath);

            $this->info("📊 Found " . count($csvData) . " seasons in CSV");

            $imported = 0;
            $skipped = 0;
            $updated = 0;

            foreach ($csvData as $row) {
                $name = trim($row['name']);
                $description = trim($row['description'] ?? '');

                if (empty($name)) {
                    $this->warn("⚠️  Skipping row with empty name");
                    continue;
                }

                if (isset($existingLookup[$name])) {
                    // Check if description needs updating
                    $existingDesc = $existingLookup[$name]['description'];
                    if ($existingDesc !== $description) {
                        if ($dryRun) {
                            $this->line("📝 Would update season: {$name} (description changed)");
                            $updated++;
                        } else {
                            $this->line("🔄 Updating season: {$name}");
                            $result = $farmOSApi->updateSeasonTerm($existingLookup[$name]['id'], $name, $description);
                            if ($result['success']) {
                                $this->info("✅ Updated season: {$name}");
                                $updated++;
                            } else {
                                $this->error("❌ Failed to update season: {$name} - " . ($result['error'] ?? 'Unknown error'));
                            }
                        }
                    } else {
                        $this->line("⏭️  Skipping unchanged season: {$name}");
                        $skipped++;
                    }
                    continue;
                }

                // Create new season
                if ($dryRun) {
                    $this->line("📝 Would create season: {$name}");
                    $imported++;
                    continue;
                }

                $this->line("🌱 Creating season: {$name}");

                $result = $farmOSApi->createSeasonTerm($name, $description);

                if ($result['success']) {
                    $this->info("✅ Created season: {$name}");
                    $imported++;
                } else {
                    $this->error("❌ Failed to create season: {$name} - " . ($result['error'] ?? 'Unknown error'));
                }
            }

            $this->info("🎉 Import complete!");
            $this->info("📊 Seasons created: {$imported}");
            $this->info("🔄 Seasons updated: {$updated}");
            $this->info("⏭️  Seasons skipped: {$skipped}");

            if ($dryRun) {
                $this->info("💡 This was a dry run. Use without --dry-run to actually import.");
            }

        } catch (\Exception $e) {
            $this->error('❌ Import failed: ' . $e->getMessage());
            Log::error('FarmOS seasons import failed', [
                'error' => $e->getMessage(),
                'file' => $filePath
            ]);
            return 1;
        }

        return 0;
    }

    /**
     * Read CSV file and return array of rows
     */
    private function readCsv(string $filePath): array
    {
        $data = [];
        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            throw new \Exception("Could not open CSV file: {$filePath}");
        }

        // Read header
        $header = fgetcsv($handle);
        if (!$header) {
            throw new \Exception("Could not read CSV header");
        }

        // Convert header to lowercase for consistency
        $header = array_map('strtolower', $header);

        // Read data rows
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) === count($header)) {
                $data[] = array_combine($header, $row);
            }
        }

        fclose($handle);

        return $data;
    }
}