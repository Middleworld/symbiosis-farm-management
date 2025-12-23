<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CsvDatasetImporter;
use Illuminate\Support\Facades\File;

class ImportDataset extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dataset:import 
                            {path : Path to CSV file or directory containing CSV files}
                            {--table= : Custom table name (optional)}
                            {--delimiter=, : CSV delimiter (default: comma)}
                            {--drop : Drop table if exists}
                            {--recursive : Scan directory recursively}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import CSV datasets into PostgreSQL tables';

    protected $importer;

    public function __construct(CsvDatasetImporter $importer)
    {
        parent::__construct();
        $this->importer = $importer;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $path = $this->argument('path');
        
        if (!file_exists($path)) {
            $this->error("Path does not exist: {$path}");
            return 1;
        }
        
        $this->info("🚀 Starting CSV Dataset Import");
        $this->info("Path: {$path}");
        $this->newLine();
        
        if (is_file($path)) {
            // Import single file
            $this->importFile($path);
        } else {
            // Import directory
            $this->importDirectory($path);
        }
        
        $this->newLine();
        $this->info("✅ Import complete!");
        
        // Show imported datasets
        $this->showImportedDatasets();
        
        return 0;
    }
    
    /**
     * Import a single CSV file
     */
    protected function importFile(string $filePath): void
    {
        if (pathinfo($filePath, PATHINFO_EXTENSION) !== 'csv') {
            $this->warn("Skipping non-CSV file: " . basename($filePath));
            return;
        }
        
        $this->info("📊 Importing: " . basename($filePath));
        
        $options = [
            'delimiter' => $this->option('delimiter'),
            'drop_if_exists' => $this->option('drop')
        ];
        
        $tableName = $this->option('table');
        
        try {
            $result = $this->importer->import($filePath, $tableName, $options);
            
            $this->info("   ✓ Table: {$result['table_name']}");
            $this->info("   ✓ Rows imported: " . number_format($result['rows_imported']));
            $this->info("   ✓ Columns: {$result['columns']} (" . implode(', ', array_slice($result['column_names'], 0, 5)) . "...)");
            
        } catch (\Exception $e) {
            $this->error("   ✗ Failed: " . $e->getMessage());
        }
        
        $this->newLine();
    }
    
    /**
     * Import all CSV files from a directory
     */
    protected function importDirectory(string $dirPath): void
    {
        $recursive = $this->option('recursive');
        
        $pattern = $recursive ? $dirPath . '/**/*.csv' : $dirPath . '/*.csv';
        $files = File::glob($pattern);
        
        if (empty($files)) {
            $this->warn("No CSV files found in: {$dirPath}");
            return;
        }
        
        $this->info("Found " . count($files) . " CSV file(s)");
        $this->newLine();
        
        $progressBar = $this->output->createProgressBar(count($files));
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');
        
        $imported = 0;
        $failed = 0;
        
        foreach ($files as $file) {
            $progressBar->setMessage(basename($file));
            
            try {
                $result = $this->importer->import($file, null, [
                    'delimiter' => $this->option('delimiter'),
                    'drop_if_exists' => $this->option('drop')
                ]);
                $imported++;
            } catch (\Exception $e) {
                $failed++;
                $this->newLine();
                $this->error("Failed to import " . basename($file) . ": " . $e->getMessage());
            }
            
            $progressBar->advance();
        }
        
        $progressBar->finish();
        $this->newLine(2);
        
        $this->info("Summary:");
        $this->info("  ✓ Successfully imported: {$imported}");
        if ($failed > 0) {
            $this->warn("  ✗ Failed: {$failed}");
        }
    }
    
    /**
     * Show all imported datasets
     */
    protected function showImportedDatasets(): void
    {
        $datasets = $this->importer->getImportedDatasets();
        
        if (empty($datasets)) {
            return;
        }
        
        $this->info("📚 Imported Datasets:");
        $this->newLine();
        
        $headers = ['Table', 'Display Name', 'Rows', 'Columns'];
        $rows = [];
        
        foreach ($datasets as $dataset) {
            $rows[] = [
                $dataset['table_name'],
                $dataset['display_name'],
                number_format($dataset['row_count']),
                $dataset['column_count']
            ];
        }
        
        $this->table($headers, $rows);
    }
}
