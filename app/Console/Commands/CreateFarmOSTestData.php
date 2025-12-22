<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CreateFarmOSTestData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'farmos:create-test-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create safe test data for FarmOS integration (all prefixed with TEST-)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🌱 Creating FarmOS test data...');
        
        $this->call('db:seed', ['--class' => 'FarmOSTestDataSeeder']);
        
        $this->newLine();
        $this->info('✅ Test data created successfully!');
        $this->warn('💡 Remember: All test data can be safely removed with: php artisan farmos:clear-test-data');
        
        return 0;
    }
}
