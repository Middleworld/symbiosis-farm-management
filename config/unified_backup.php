<?php

return [
    // Unified backup configuration for all sites
    'sites' => [
        'admin' => [
            'type' => 'custom',
            'enabled' => true,
            'label' => 'Admin (Laravel)',
            'command' => 'backup:custom',
        ],
        'farmos' => [
            'type' => 'custom',
            'enabled' => true,
            'label' => 'FarmOS',
            'command' => 'backup:farmos',
        ],
        'carey-one' => [
            'type' => 'custom',
            'enabled' => true,
            'label' => 'Carey One (Main Website)',
            'command' => 'backup:carey-one',
        ],
        // middleworld.farm remote API not implemented yet
        // 'middleworld.farm' => [
        //     'type' => 'remote_api',
        //     'enabled' => false,
        //     'label' => 'Middleworld Farm',
        //     'api_url' => 'https://middleworld.farm/api/backup',
        //     'api_token' => env('MWFARM_BACKUP_API_TOKEN'),
        // ],
    ],
    // Where to store all unified backups (relative to storage/app)
    'storage_path' => 'backups/unified',
    // Retention policy (in days)
    'retention_days' => 30,
];
