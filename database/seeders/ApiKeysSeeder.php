<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ApiKeysSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $apiKeys = [
            'farmos_username' => env('FARMOS_USERNAME', 'admin'),
            'farmos_password' => env('FARMOS_PASSWORD', ''),
            'farmos_oauth_client_id' => env('FARMOS_OAUTH_CLIENT_ID', ''),
            'farmos_oauth_client_secret' => env('FARMOS_OAUTH_CLIENT_SECRET', ''),
            'woocommerce_consumer_key' => env('WOOCOMMERCE_CONSUMER_KEY', ''),
            'woocommerce_consumer_secret' => env('WOOCOMMERCE_CONSUMER_SECRET', ''),
            'mwf_api_key' => env('MWF_API_KEY', ''),
            'google_maps_api_key' => env('GOOGLE_MAPS_API_KEY', ''),
            'met_office_api_key' => env('MET_OFFICE_API_KEY', ''),
            'openweather_api_key' => env('OPENWEATHER_API_KEY', ''),
            'huggingface_api_key' => env('HUGGINGFACE_API_KEY', ''),
            'stripe_key' => env('STRIPE_KEY', ''),
            'stripe_secret' => env('STRIPE_SECRET', ''),
        ];

        foreach ($apiKeys as $key => $value) {
            if (!empty($value)) {
                // Use the SettingsController's encryption method
                $encryptedValue = $this->encryptApiKey($value);
                \App\Models\Setting::set($key, $encryptedValue, 'string', $this->getApiKeyDescription($key));
            }
        }

        $this->command->info('API keys seeded successfully!');
    }

    /**
     * Get API key description
     */
    private function getApiKeyDescription(string $key): string
    {
        $descriptions = [
            'farmos_username' => 'FarmOS admin username for API authentication',
            'farmos_password' => 'FarmOS admin password for API authentication',
            'farmos_oauth_client_id' => 'FarmOS OAuth2 client ID for API access',
            'farmos_oauth_client_secret' => 'FarmOS OAuth2 client secret for API access',
            'woocommerce_consumer_key' => 'WooCommerce REST API consumer key',
            'woocommerce_consumer_secret' => 'WooCommerce REST API consumer secret',
            'mwf_api_key' => 'Middle World Farms integration API key',
            'google_maps_api_key' => 'Google Maps JavaScript API key',
            'met_office_api_key' => 'UK Met Office Weather API key',
            'openweather_api_key' => 'OpenWeatherMap API key',
            'huggingface_api_key' => 'Hugging Face Inference API key',
            'stripe_key' => 'Stripe publishable key (pk_...)',
            'stripe_secret' => 'Stripe secret key (sk_...)',
        ];

        return $descriptions[$key] ?? 'API key for external service integration';
    }
    
    /**
     * Encrypt API key for secure storage
     */
    private function encryptApiKey(string $key): string
    {
        return encrypt($key);
    }
}
