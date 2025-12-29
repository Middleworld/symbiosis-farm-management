<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Weather Service Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for weather data providers including Met Office and
    | OpenWeatherMap APIs for agricultural weather intelligence.
    |
    */

    'apis' => [
        'met_office' => [
            'api_key' => env('MET_OFFICE_API_KEY', ''),
            'base_url' => 'https://api-metoffice.apiconnect.ibmcloud.com/metoffice/production/v0',
            'enabled' => !empty(env('MET_OFFICE_API_KEY')),
            'priority' => 1, // Higher priority for UK weather accuracy
        ],
        
        'openweathermap' => [
            'api_key' => env('OPENWEATHERMAP_API_KEY', ''),
            'base_url' => 'https://api.openweathermap.org/data/2.5',
            'historical_url' => 'https://api.openweathermap.org/data/3.0/onecall/timemachine',
            'enabled' => !empty(env('OPENWEATHERMAP_API_KEY')),
            'priority' => 2, // Lower priority but good for historical data
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Location Configuration
    |--------------------------------------------------------------------------
    |
    | Default location coordinates and settings for weather data retrieval.
    |
    */

    'location' => [
        'latitude' => env('WEATHER_LATITUDE', 51.4934), // Greenwich, London
        'longitude' => env('WEATHER_LONGITUDE', 0.0098),
        'name' => env('WEATHER_LOCATION_NAME', 'Greenwich, London'),
        'timezone' => 'Europe/London',
        'elevation' => 46, // meters above sea level
    ],

    /*
    |--------------------------------------------------------------------------
    | Agricultural Settings
    |--------------------------------------------------------------------------
    |
    | Settings specific to agricultural weather calculations and thresholds.
    |
    */

    'agriculture' => [
        'growing_season_start' => '03-01', // March 1st
        'growing_season_end' => '10-31',   // October 31st
        
        'temperature_thresholds' => [
            'frost_warning' => 2.0,      // °C - issue frost warning
            'frost_critical' => 0.0,     // °C - critical frost level
            'heat_stress' => 30.0,       // °C - heat stress threshold
            'gdd_base' => 10.0,          // °C - base temperature for GDD
            'gdd_ceiling' => 30.0,       // °C - ceiling temperature for GDD
        ],
        
        'field_work_conditions' => [
            'max_wind_speed' => 25.0,     // mph - maximum wind for field work
            'min_visibility' => 1000.0,   // meters - minimum visibility
            'rain_threshold' => 0.5,      // mm/hour - rain threshold
            'soil_temp_min' => 5.0,       // °C - minimum soil temperature
        ],
        
        'planting_windows' => [
            'frost_free_days' => 14,      // days - required frost-free period
            'soil_workability_days' => 3, // days - required dry conditions
            'temperature_stability' => 7, // days - consistent temperature period
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Caching Configuration
    |--------------------------------------------------------------------------
    |
    | Cache settings for weather data to reduce API calls and improve performance.
    |
    */

    'cache' => [
        'current_weather_ttl' => 10 * 60,    // 10 minutes
        'forecast_ttl' => 60 * 60,           // 1 hour
        'historical_ttl' => 24 * 60 * 60,    // 24 hours
        'gdd_ttl' => 60 * 60,                // 1 hour
        'alerts_ttl' => 5 * 60,              // 5 minutes
        'prefix' => 'weather',
    ],

    /*
    |--------------------------------------------------------------------------
    | API Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Rate limiting configuration to stay within API provider limits.
    |
    */

    'rate_limits' => [
        'met_office' => [
            'requests_per_minute' => 60,
            'requests_per_day' => 5000,
        ],
        'openweathermap' => [
            'requests_per_minute' => 60,
            'requests_per_day' => 1000,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Fallback Configuration
    |--------------------------------------------------------------------------
    |
    | Fallback settings when primary weather services are unavailable.
    |
    */

    'fallback' => [
        'enabled' => true,
        'mock_data' => false, // Set to true for testing
        'default_conditions' => [
            'temperature' => 15.0,
            'humidity' => 70,
            'wind_speed' => 5.0,
            'pressure' => 1013.25,
            'description' => 'Unknown',
            'condition' => 'cloudy',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Alert Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for weather alerts and notifications.
    |
    */

    'alerts' => [
        'frost_enabled' => true,
        'heat_enabled' => true,
        'wind_enabled' => true,
        'precipitation_enabled' => true,
        
        'notification_channels' => [
            'dashboard' => true,
            'email' => false,
            'sms' => false,
        ],
        
        'severity_levels' => [
            'info' => 'blue',
            'warning' => 'orange',
            'critical' => 'red',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Units Configuration
    |--------------------------------------------------------------------------
    |
    | Default units for weather data display and calculations.
    |
    */

    'units' => [
        'temperature' => 'celsius',     // celsius, fahrenheit
        'wind_speed' => 'mph',         // mph, kmh, ms
        'pressure' => 'mb',            // mb, inhg, pa
        'precipitation' => 'mm',       // mm, inches
        'distance' => 'km',            // km, miles
    ],

    /*
    |--------------------------------------------------------------------------
    | Historical Data Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for historical weather data analysis and storage.
    |
    */

    'historical' => [
        'max_years_back' => 10,        // Maximum years of historical data
        'analysis_periods' => [
            'last_week' => 7,
            'last_month' => 30,
            'last_season' => 180,
            'last_year' => 365,
        ],
        'climate_normals_period' => 30, // Years for climate normal calculations
    ],
];
