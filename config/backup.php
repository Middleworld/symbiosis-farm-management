<?php

return [

    /*
     * The name of this application. You can use this name to monitor
     * the backups.
     */
    'name' => env('APP_NAME', 'Laravel'),

    'source' => [

        'files' => [

            /*
             * The list of directories and files that will be included in the backup.
             */
            /*
             * The list of directories and files that will be included in the backup.
             */
            'include' => [
                base_path('app'),           // ✅ Custom application code
                base_path('config'),        // ✅ Configuration files
                base_path('database'),      // ✅ Migrations and seeds
                base_path('resources'),     // ✅ Views and assets
                base_path('routes'),        // ✅ Custom routes
                base_path('public'),        // ✅ Public assets
                base_path('docs'),          // ✅ Documentation
                base_path('data'),          // ✅ Custom data
                base_path('assets'),        // ✅ Custom assets
                base_path('.env.example'),  // ✅ Environment template
                base_path('composer.json'), // ✅ Dependencies manifest
                base_path('package.json'),  // ✅ NPM dependencies manifest
                base_path('artisan'),       // ✅ Laravel artisan file
                base_path('README.md'),     // ✅ Documentation
            ],

            /*
             * These directories and files will be excluded from the backup.
             *
             * Directories used by the backup process will automatically be excluded.
             */
            'exclude' => [
                // ❌ Third-party/vendor directories (can be reinstalled)
                base_path('vendor'),              // Composer dependencies
                base_path('node_modules'),        // NPM dependencies
                base_path('ai_service'),          // AI service (can be recreated)
                base_path('venv'),                // Python virtual environments

                // ❌ Version control and development
                base_path('.git'),               // Git repository
                base_path('.idea'),              // IDE files

                // ❌ Storage and cache (regenerated data)
                base_path('storage'),            // All storage (logs, cache, backups)
                base_path('bootstrap/cache'),    // Compiled views and routes

                // ❌ Testing and development
                base_path('dev-testing'),        // Development test files
                base_path('tests'),              // Test files

                // ❌ Legacy/processed data
                base_path('ingest'),             // Processed ingestion data
            ],

            /*
             * Determines if symlinks should be followed.
             */
            'follow_links' => false,

            /*
             * Note that this option is only used for the "include" paths.
             * The "exclude" paths will always be followed by default.
             */
        ],

        'databases' => [

            /*
             * The names of the connections to the databases that should be backed up
             * Only MySQL and PostgreSQL databases are supported.
             */
            'include' => [
                'mysql',
            ],

            /*
             * If you are using only InnoDB tables on a MySQL server, you can
             * also supply the --single-transaction option to the mysqldump command.
             * This makes sure the backup won't hang when database migrations are running.
             * Do not forget to add the --single-transaction option to the dump_command below.
             */
            'mysql' => [
                'dump_command_path' => '/usr/bin', // or null
                'dump_command_timeout' => 60 * 5, // 5 minute timeout
                'chunk_size_in_mb' => 200,
                'use_single_transaction' => true,
                'exclude_tables' => [],
            ],

            'postgresql' => [
                'dump_command_path' => '/usr/bin', // or null
                'dump_command_timeout' => 60 * 5, // 5 minute timeout
                'chunk_size_in_mb' => 200,
                'exclude_tables' => [],
            ],
        ],
    ],

    'destination' => [

        'disks' => [
            'backups',  // Use dedicated backups disk, not local
        ],
    ],

    'temporary_directory' => storage_path('app/backup-temp'),

    'password' => env('BACKUP_PASSWORD'),

    'encryption' => 'default',

    'notifications' => [

        'notifications' => [
            \Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification::class => ['mail'],
            \Spatie\Backup\Notifications\Notifications\UnhealthyBackupWasFoundNotification::class => ['mail'],
            \Spatie\Backup\Notifications\Notifications\CleanupHasFailedNotification::class => ['mail'],
            \Spatie\Backup\Notifications\Notifications\BackupWasSuccessfulNotification::class => ['mail'],
            \Spatie\Backup\Notifications\Notifications\HealthyBackupWasFoundNotification::class => ['mail'],
        ],

        'notifiable' => \Spatie\Backup\Notifications\Notifiable::class,

        'mail' => [
            'to' => 'your-email@example.com',
            'from' => [
                'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
                'name' => env('MAIL_FROM_NAME', 'Example'),
            ],
        ],

        'slack' => [
            'webhook_url' => '',
            'channel' => null,
            'username' => null,
            'icon' => null,
        ],

    ],

    'scripts' => [
        'pre-backup' => [
            // Estimate backup size and show progress info
            'echo "=== BACKUP SIZE ESTIMATE ===" && ' .
            'TOTAL_SIZE=$(du -sh ' . base_path('app') . ' ' . base_path('config') . ' ' . base_path('database') . ' ' . base_path('resources') . ' ' . base_path('routes') . ' ' . base_path('public') . ' ' . base_path('docs') . ' ' . base_path('data') . ' ' . base_path('assets') . ' 2>/dev/null | awk \'{sum += $1} END {print sum}\') && ' .
            'echo "Estimated backup size: ~${TOTAL_SIZE} (custom code only)" && ' .
            'echo "Excluded: vendor, node_modules, ai_service, storage, .git (~14GB total)" && ' .
            'echo "Expected time: 2-5 minutes for ~10-20MB backup" && ' .
            'echo "Progress will be shown during zipping..." && ' .
            'echo "=====================================" && ' .
            // Clean up old backup files before creating new ones
            'find ' . storage_path('backups') . ' -name "*.zip" -mtime +7 -delete 2>/dev/null || true',
        ],
        'post-backup' => [
            // Show actual backup size and completion time
            'echo "=== BACKUP COMPLETED ===" && ' .
            'LATEST_BACKUP=$(find ' . storage_path('backups') . ' -name "*.zip" -type f -printf "%T@ %p\n" 2>/dev/null | sort -n | tail -1 | cut -d" " -f2-) && ' .
            'if [ -n "$LATEST_BACKUP" ]; then ACTUAL_SIZE=$(du -sh "$LATEST_BACKUP" | cut -f1); echo "Actual backup size: $ACTUAL_SIZE"; fi && ' .
            'echo "Backup location: ' . storage_path('backups') . '" && ' .
            'echo "Completed at: $(date)" && ' .
            'echo "===========================" && ' .
            // Log backup completion
            'echo "Laravel Admin backup completed at $(date) - Size: $ACTUAL_SIZE" >> /var/log/laravel-backup.log',
        ],
    ],
];
