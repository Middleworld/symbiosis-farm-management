<?php

/**
 * API Key Service Usage Examples
 *
 * This file demonstrates how to use the ApiKeyService throughout the application
 * to securely retrieve API keys from the database instead of .env files.
 */

// Example 1: Basic API key retrieval
$farmosUsername = \App\Services\ApiKeyService::get('farmos_username');
$googleMapsKey = \App\Services\ApiKeyService::get('google_maps_api_key');

// Example 2: Get all API keys
$allKeys = \App\Services\ApiKeyService::getAll();

// Example 3: Service-specific credential retrieval
$farmOsCredentials = \App\Services\ApiKeyService::getFarmOsCredentials();
$wooCommerceCredentials = \App\Services\ApiKeyService::getWooCommerceCredentials();
$stripeKeys = \App\Services\ApiKeyService::getStripeKeys();
$weatherKeys = \App\Services\ApiKeyService::getWeatherApiKeys();

// Example 4: Check if API key is configured
if (\App\Services\ApiKeyService::hasKey('google_maps_api_key')) {
    // Use Google Maps integration
    $mapsKey = \App\Services\ApiKeyService::getGoogleMapsApiKey();
}

// Example 5: Get API key status for all services
$status = \App\Services\ApiKeyService::getStatus();
// Returns array with status for each service (complete/partial/missing)

// Example 6: Using in a controller
class WeatherController extends Controller
{
    public function getWeatherData()
    {
        $weatherKeys = \App\Services\ApiKeyService::getWeatherApiKeys();

        if (empty($weatherKeys['met_office']) && empty($weatherKeys['openweather'])) {
            return response()->json(['error' => 'No weather API keys configured'], 400);
        }

        // Use the API keys...
        return $this->fetchWeatherData($weatherKeys);
    }
}

// Example 7: Using in a service class
class FarmOsService
{
    public function authenticate()
    {
        $credentials = \App\Services\ApiKeyService::getFarmOsCredentials();

        // Use OAuth2 flow with the credentials from database
        return $this->performOAuth2Authentication($credentials);
    }
}

// Example 8: Migration path - gradually replace env() calls
// OLD WAY (insecure):
$oldApiKey = env('GOOGLE_MAPS_API_KEY');

// NEW WAY (secure):
$newApiKey = \App\Services\ApiKeyService::getGoogleMapsApiKey();

// With fallback to .env for gradual migration:
$apiKey = \App\Services\ApiKeyService::get('google_maps_api_key') ?: env('GOOGLE_MAPS_API_KEY');

// Example 9: In Blade templates
// In your view files, you can access via the controller or service
/*
@php
    $googleMapsKey = \App\Services\ApiKeyService::getGoogleMapsApiKey();
@endphp

<script>
    // Use the API key in JavaScript
    const googleMapsKey = '{{ $googleMapsKey }}';
    // Initialize Google Maps...
</script>
*/
