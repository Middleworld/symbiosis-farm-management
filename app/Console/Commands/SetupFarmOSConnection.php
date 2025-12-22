<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FarmOSApi;

class SetupFarmOSConnection extends Command
{
    protected $signature = 'farmos:setup';
    protected $description = 'Test and setup farmOS API connection';

    public function handle()
    {
        $this->info('🚜 Setting up farmOS API connection...');
        
        // Check environment variables
        $url = config('farmos.url');
        $username = config('farmos.username');
        $password = config('farmos.password');
        $clientId = config('farmos.client_id');
        $clientSecret = config('farmos.client_secret');
        
        $this->info("📍 farmOS URL: " . ($url ?: '❌ Not set'));
        $this->info("👤 Username: " . ($username ? '✅ Set' : '❌ Not set'));
        $this->info("🔑 Password: " . ($password ? '✅ Set' : '❌ Not set'));
        $this->info("🔐 Client ID: " . ($clientId ?: '❌ Not set'));
        $this->info("🔒 Client Secret: " . ($clientSecret ? '✅ Set' : '❌ Not set'));
        
        if (!$username || !$password) {
            $this->error('❌ Missing farmOS credentials in .env file');
            $this->line('');
            $this->line('Please add the following to your .env file:');
            $this->line('FARMOS_USERNAME=your_farmos_username');
            $this->line('FARMOS_PASSWORD=your_farmos_password');
            $this->line('FARMOS_CLIENT_SECRET=your_oauth_client_secret');
            $this->line('');
            $this->line('You can find/create OAuth credentials in your farmOS admin panel.');
            return 1;
        }
        
        // Test connection
        try {
            $service = new FarmOSApi();
            
            $this->info('🔄 Testing authentication...');
            $token = $service->authenticate();
            
            if ($token) {
                $this->info('✅ Authentication successful!');
                
                $this->info('🗺️ Testing land assets access...');
                $geometryData = $service->getGeometryAssets();
                
                if (isset($geometryData['auth_issue'])) {
                    $this->warn('⚠️ Authentication successful but access denied to land assets');
                    $this->warn('Available assets: ' . ($geometryData['available_assets'] ?? 0));
                    $this->line('The farmOS user needs permission to view land assets via API.');
                } elseif (isset($geometryData['features'])) {
                    $featureCount = count($geometryData['features']);
                    $this->info("✅ Successfully loaded {$featureCount} land features!");
                    
                    if ($featureCount > 0) {
                        $this->line('');
                        $this->line('🗺️ Found land assets:');
                        foreach (array_slice($geometryData['features'], 0, 5) as $feature) {
                            $name = $feature['properties']['name'] ?? 'Unnamed';
                            $this->line("  • {$name}");
                        }
                        if ($featureCount > 5) {
                            $this->line("  ... and " . ($featureCount - 5) . " more");
                        }
                    }
                } else {
                    $this->warn('⚠️ No land assets found in farmOS');
                }
                
            } else {
                $this->error('❌ Authentication failed');
                $this->line('Check your farmOS credentials and OAuth setup.');
                return 1;
            }
            
        } catch (\Exception $e) {
            $this->error('❌ Connection failed: ' . $e->getMessage());
            return 1;
        }
        
        $this->info('');
        $this->info('🎉 farmOS connection setup complete!');
        return 0;
    }
}
