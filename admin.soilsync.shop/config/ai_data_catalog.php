<?php
return [
    'version' => 2,
    'services' => [
        'farmos' => [
            'description' => 'farmOS API integration (expanded) for assets, logs, taxonomy',
            'class' => \App\Services\FarmOSApi::class,
            'methods' => [
                // Read-only (tier: public)
                'getGeometryAssets' => ['desc' => 'GeoJSON FeatureCollection for land/bed polygons', 'tier' => 'public'],
                'getPlantAssets' => ['desc' => 'Active plant assets (paginated aggregated)', 'tier' => 'public'],
                'getLandAssets' => ['desc' => 'Raw land assets list (JSON:API data array)', 'tier' => 'public'],
                'getHarvestLogs' => ['desc' => 'Harvest logs (recent, status done)', 'tier' => 'public'],
                'getObservationLogs' => ['desc' => 'Observation logs', 'tier' => 'public'],
                'getActivityLogs' => ['desc' => 'Activity logs', 'tier' => 'public'],
                'getInputLogs' => ['desc' => 'Input logs (fertility, amendments)', 'tier' => 'public'],
                'getSeedingLogs' => ['desc' => 'Seeding logs', 'tier' => 'public'],
                'getTransplantingLogs' => ['desc' => 'Transplanting logs', 'tier' => 'public'],
                'getPlantTypes' => ['desc' => 'Plant type taxonomy terms', 'tier' => 'public'],
                'getVarieties' => ['desc' => 'Variety taxonomy terms', 'tier' => 'public'],
                'getCropFamilies' => ['desc' => 'Crop family taxonomy terms', 'tier' => 'public'],
                'getLocations' => ['desc' => 'Location taxonomy terms', 'tier' => 'public'],
                'getFullDataSnapshot' => ['desc' => 'Aggregate snapshot of key farmOS resources', 'tier' => 'public'],
                // Trusted write (no manager approval required)
                'createPlantAsset' => ['desc' => 'Create a new plant asset (trusted_public_write)', 'tier' => 'trusted_public_write', 'status' => 'planned']
            ],
            'env' => ['FARMOS_CLIENT_ID','FARMOS_CLIENT_SECRET','FARMOS_OAUTH_CLIENT_ID','FARMOS_OAUTH_CLIENT_SECRET','FARMOS_URL']
        ],
        'woocommerce' => [
            'description' => 'WooCommerce + WordPress integration (read-only AI surface)',
            'class' => \App\Services\WpApiService::class,
            'methods' => [
                'getRecentUsers' => ['desc' => 'Recent WooCommerce customers (redacted)', 'tier' => 'restricted'],
                'searchUsers' => ['desc' => 'Search users by term (redacted)', 'tier' => 'restricted'],
                'searchUsersByEmail' => ['desc' => 'Exact match by email (redacted)', 'tier' => 'restricted'],
                'getDeliveryScheduleData' => ['desc' => 'Delivery schedule aggregation', 'tier' => 'restricted']
            ],
            'env' => ['WOOCOMMERCE_URL','WOOCOMMERCE_CONSUMER_KEY','WOOCOMMERCE_CONSUMER_SECRET','SELF_SERVE_SHOP_INTEGRATION_KEY']
        ],
        'planting' => [
            'description' => 'Internal static crop schedule + week logic',
            'class' => \App\Services\PlantingRecommendationService::class,
            'methods' => [
                'forWeek' => ['desc' => 'Current (or provided) ISO week planting recommendation JSON', 'tier' => 'public']
            ],
            'config' => ['config/planting_schedule.php']
        ]
    ],
    'notes' => [
        'Each method declares a tier: public, restricted, trusted_public_write.',
        'restricted = fields must be redacted before AI consumption.',
        'trusted_public_write = allowed create/update without manager review (limited scope).',
        'No sensitive write methods exposed yet.',
        'createPlantAsset currently status=planned – implement before enabling real writes.'
    ]
];
