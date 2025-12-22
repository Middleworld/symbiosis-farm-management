<?php

return [
    /*
    |--------------------------------------------------------------------------
    | FarmOS Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for FarmOS API integration to sync harvest logs
    | and automatically update stock levels in the admin system.
    |
    */

    'url' => env('FARMOS_URL', 'https://farmos.middleworldfarms.org'),
    
    'username' => env('FARMOS_USERNAME'),
    
    'password' => env('FARMOS_PASSWORD'),
    
    'client_id' => env('FARMOS_OAUTH_CLIENT_ID'),
    
    'client_secret' => env('FARMOS_OAUTH_CLIENT_SECRET'),
    
    'oauth_scope' => env('FARMOS_OAUTH_SCOPE', 'farm_manager'),
    
    /*
    |--------------------------------------------------------------------------
    | Sync Settings
    |--------------------------------------------------------------------------
    */
    
    'auto_sync_enabled' => env('FARMOS_AUTO_SYNC', true),
    
    'sync_interval_minutes' => env('FARMOS_SYNC_INTERVAL', 15),
    
    'sync_harvest_logs' => env('FARMOS_SYNC_HARVEST_LOGS', true),
    
    'sync_plant_assets' => env('FARMOS_SYNC_PLANT_ASSETS', true),
    
    /*
    |--------------------------------------------------------------------------
    | Stock Management
    |--------------------------------------------------------------------------
    */
    
    'auto_update_stock' => env('FARMOS_AUTO_UPDATE_STOCK', true),
    
    'default_stock_units' => env('FARMOS_DEFAULT_UNITS', 'kg'),
    
    'stock_location' => env('FARMOS_STOCK_LOCATION', 'Main Store'),
    
    /*
    |--------------------------------------------------------------------------
    | Crop Planning Integration
    |--------------------------------------------------------------------------
    */
    
    'crop_planning_enabled' => env('FARMOS_CROP_PLANNING', true),
    
    'auto_create_harvest_logs' => env('FARMOS_AUTO_CREATE_HARVEST_LOGS', false),
    
    'notification_email' => env('FARMOS_NOTIFICATION_EMAIL'),
    
];
