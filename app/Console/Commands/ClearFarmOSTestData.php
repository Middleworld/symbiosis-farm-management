<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ClearFarmOSTestData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'farmos:clear-test-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Safely remove all FarmOS test data (only removes data with TEST- prefixes)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->warn('🧹 This will remove all FarmOS test data...');
        
        $this->call('db:seed', ['--class' => 'ClearFarmOSTestDataSeeder']);
        
        return 0;
    }
}
