<?php

namespace App\Services;

use App\Models\Setting;

/**
 * Service for managing API keys stored in database
 * Provides secure access to encrypted API keys
 */
class ApiKeyService
{
    /**
     * Get a specific API key by name
     */
    public static function get(string $key): string
    {
        return \App\Http\Controllers\Admin\SettingsController::getApiKey($key);
    }

    /**
     * Get all API keys as an array
     */
    public static function getAll(): array
    {
        return \App\Http\Controllers\Admin\SettingsController::getAllApiKeys();
    }

    /**
     * Get FarmOS API credentials
     */
    public static function getFarmOsCredentials(): array
    {
        return [
            'url' => env('FARMOS_URL', 'https://farmos.middleworldfarms.org'),
            'username' => self::get('farmos_username') ?: env('FARMOS_USERNAME', 'admin'),
            'password' => self::get('farmos_password') ?: env('FARMOS_PASSWORD', ''),
            'client_id' => self::get('farmos_oauth_client_id') ?: env('FARMOS_OAUTH_CLIENT_ID', ''),
            'client_secret' => self::get('farmos_oauth_client_secret') ?: env('FARMOS_OAUTH_CLIENT_SECRET', ''),
            'scope' => env('FARMOS_OAUTH_SCOPE', 'farm_manager'),
            'auto_sync' => env('FARMOS_AUTO_SYNC', true),
        ];
    }

    /**
     * Get WooCommerce API credentials
     */
    public static function getWooCommerceCredentials(): array
    {
        return [
            'url' => env('WOOCOMMERCE_URL', 'https://middleworldfarms.org/'),
            'consumer_key' => self::get('woocommerce_consumer_key') ?: env('WOOCOMMERCE_CONSUMER_KEY', ''),
            'consumer_secret' => self::get('woocommerce_consumer_secret') ?: env('WOOCOMMERCE_CONSUMER_SECRET', ''),
        ];
    }

    /**
     * Get MWF Integration API key
     */
    public static function getMwfApiKey(): string
    {
        return self::get('mwf_api_key') ?: env('MWF_API_KEY', '');
    }

    /**
     * Get Google Maps API key
     */
    public static function getGoogleMapsApiKey(): string
    {
        return self::get('google_maps_api_key') ?: env('GOOGLE_MAPS_API_KEY', '');
    }

    /**
     * Get weather API keys
     */
    public static function getWeatherApiKeys(): array
    {
        return [
            'met_office' => self::get('met_office_api_key') ?: env('MET_OFFICE_API_KEY', ''),
            'met_office_land_observations' => self::get('met_office_land_observations_key') ?: env('MET_OFFICE_LAND_OBSERVATIONS_KEY', ''),
            'met_office_site_specific' => self::get('met_office_site_specific_key') ?: env('MET_OFFICE_SITE_SPECIFIC_KEY', ''),
            'met_office_atmospheric' => self::get('met_office_atmospheric_key') ?: env('MET_OFFICE_ATMOSPHERIC_KEY', ''),
            'met_office_map_images' => self::get('met_office_map_images_key') ?: env('MET_OFFICE_MAP_IMAGES_KEY', ''),
            'openweather' => self::get('openweather_api_key') ?: env('OPENWEATHER_API_KEY', ''),
            'latitude' => env('WEATHER_LATITUDE', env('FARM_LATITUDE', '51.4934')),
            'longitude' => env('WEATHER_LONGITUDE', env('FARM_LONGITUDE', '0.0098')),
            'location_name' => env('WEATHER_LOCATION_NAME', 'Greenwich, London'),
        ];
    }

    /**
     * Get Hugging Face API key
     */
    public static function getHuggingFaceApiKey(): string
    {
        return self::get('huggingface_api_key') ?: env('HUGGINGFACE_API_KEY', '');
    }

    /**
     * Get Stripe API keys
     */
    public static function getStripeKeys(): array
    {
        return [
            'publishable' => self::get('stripe_key') ?: env('STRIPE_KEY', ''),
            'secret' => self::get('stripe_secret') ?: env('STRIPE_SECRET', ''),
        ];
    }

    /**
     * Check if an API key is configured
     */
    public static function hasKey(string $key): bool
    {
        return !empty(self::get($key));
    }

    /**
     * Get API key status for all services
     */
    public static function getStatus(): array
    {
        $services = [
            'FarmOS' => [
                'username' => self::hasKey('farmos_username'),
                'password' => self::hasKey('farmos_password'),
                'oauth_client_id' => self::hasKey('farmos_oauth_client_id'),
                'oauth_client_secret' => self::hasKey('farmos_oauth_client_secret'),
            ],
            'WooCommerce' => [
                'consumer_key' => self::hasKey('woocommerce_consumer_key'),
                'consumer_secret' => self::hasKey('woocommerce_consumer_secret'),
            ],
            'MWF Integration' => [
                'api_key' => self::hasKey('mwf_api_key'),
            ],
            'Google Maps' => [
                'api_key' => self::hasKey('google_maps_api_key'),
            ],
            'Weather APIs' => [
                'met_office' => self::hasKey('met_office_api_key'),
                'openweather' => self::hasKey('openweather_api_key'),
            ],
            'Hugging Face' => [
                'api_key' => self::hasKey('huggingface_api_key'),
            ],
            'Stripe' => [
                'publishable_key' => self::hasKey('stripe_key'),
                'secret_key' => self::hasKey('stripe_secret'),
            ],
        ];

        // Calculate overall status for each service
        foreach ($services as $serviceName => &$serviceKeys) {
            $totalKeys = count($serviceKeys);
            $configuredKeys = count(array_filter($serviceKeys));
            $serviceKeys['status'] = $configuredKeys === $totalKeys ? 'complete' :
                                   ($configuredKeys > 0 ? 'partial' : 'missing');
            $serviceKeys['configured_count'] = $configuredKeys;
            $serviceKeys['total_count'] = $totalKeys;
        }

        return $services;
    }
}
