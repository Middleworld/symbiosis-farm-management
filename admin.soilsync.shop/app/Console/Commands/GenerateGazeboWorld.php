<?php

namespace App\Console\Commands;

use App\Services\GazeboWorldService;
use Illuminate\Console\Command;

class GenerateGazeboWorld extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gazebo:generate-world
                            {--land-id= : Specific land asset ID to generate world for}
                            {--all : Generate worlds for all land assets}
                            {--sample : Generate a sample world with mock data for testing}
                            {--output= : Output directory for world files (default: storage/app/gazebo-worlds)}
                            {--robot-model= : Robot model to spawn (default: turtlebot3_waffle)}
                            {--include-navigation : Include navigation waypoints}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Gazebo world files from farmOS land assets';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $gazeboService = app(GazeboWorldService::class);

        $outputDir = $this->option('output') ?: storage_path('app/gazebo-worlds');
        $robotModel = $this->option('robot-model') ?: 'turtlebot3_waffle';
        $includeNavigation = $this->option('include-navigation');

        // Ensure output directory exists
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        if ($this->option('sample')) {
            $this->generateSampleWorld($gazeboService, $outputDir, $robotModel, $includeNavigation);
        } elseif ($this->option('all')) {
            $this->generateAllWorlds($gazeboService, $outputDir, $robotModel, $includeNavigation);
        } elseif ($this->option('land-id')) {
            $landId = $this->option('land-id');
            $this->generateSingleWorld($gazeboService, $landId, $outputDir, $robotModel, $includeNavigation);
        } else {
            $this->error('Please specify either --all, --land-id, or --sample option');
            return 1;
        }

        $this->info('Gazebo world generation completed successfully!');
        return 0;
    }

    private function generateAllWorlds(GazeboWorldService $service, string $outputDir, string $robotModel, bool $includeNavigation)
    {
        $this->info('Fetching all land assets from farmOS...');

        try {
            $landAssets = $service->getAllLandAssets();

            if (empty($landAssets)) {
                $this->warn('No land assets found in farmOS');
                return;
            }

            $this->info("Found " . count($landAssets) . " land assets");

            $progressBar = $this->output->createProgressBar(count($landAssets));
            $progressBar->start();

            foreach ($landAssets as $asset) {
                $worldFile = $service->generateWorldFromLandAsset(
                    $asset,
                    $outputDir,
                    $robotModel,
                    $includeNavigation
                );

                if ($worldFile) {
                    $this->info("Generated world: " . basename($worldFile));
                }

                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine();

        } catch (\Exception $e) {
            $this->error('Failed to fetch land assets: ' . $e->getMessage());
        }
    }

    private function generateSampleWorld(GazeboWorldService $service, string $outputDir, string $robotModel, bool $includeNavigation)
    {
        $this->info('Generating sample world with mock farmOS data...');

        try {
            $worldFile = $service->generateSampleWorld($outputDir);

            $this->info("Sample world generated successfully: {$worldFile}");
            $this->info("You can now load this world in Gazebo with:");
            $this->info("  gazebo {$worldFile}");

        } catch (\Exception $e) {
            $this->error('Failed to generate sample world: ' . $e->getMessage());
        }
    }
}
