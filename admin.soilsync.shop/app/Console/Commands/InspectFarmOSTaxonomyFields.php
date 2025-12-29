<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FarmOSApi;

class InspectFarmOSTaxonomyFields extends Command
{
    protected $signature = 'farmos:inspect-fields';
    protected $description = 'Inspect actual field names in FarmOS plant_type taxonomy';

    public function handle()
    {
        $api = new FarmOSApi();
        
        $this->info('🔍 Inspecting FarmOS Plant Type Fields...');
        $this->newLine();
        
        try {
            $terms = $api->fetchPaginatedData('/api/taxonomy_term/plant_type', ['page' => ['limit' => 1]]);
            
            if (empty($terms)) {
                $this->warn('No plant type terms found');
                return 1;
            }
            
            $this->info('📋 Sample term: ' . ($terms[0]['attributes']['name'] ?? 'Unknown'));
            $this->newLine();
            
            $this->info('All available attributes:');
            $this->newLine();
            
            foreach ($terms[0]['attributes'] as $key => $value) {
                $valueStr = is_array($value) ? json_encode($value) : (string)$value;
                if (strlen($valueStr) > 50) {
                    $valueStr = substr($valueStr, 0, 47) . '...';
                }
                $this->line("  • <fg=cyan>{$key}</>: {$valueStr}");
            }
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }
    }
}
