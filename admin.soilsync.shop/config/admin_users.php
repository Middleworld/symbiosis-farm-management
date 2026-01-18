<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Admin Users Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the authorized admin users for the Symbiosis Admin Dashboard.
    | Each user should have a unique email and a secure password hash.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | WordPress Email Mapping
    |--------------------------------------------------------------------------
    |
    | Map Laravel admin emails to WordPress admin emails when they differ
    |
    */
    'wordpress_email_mapping' => [
        'martin@middleworldfarms.org' => 'middleworldfarms@gmail.com',
        // Example: 'admin@example-farm.com' => 'wp-admin@example-farm.com',
        // Add more mappings here if needed
    ],

    'users' => [
        [
            'name' => 'Martin Taylor',
            'email' => 'martin@middleworldfarms.org',
            'password' => 'ChangeMe123!',
            'role' => 'super_admin',
            'is_admin' => true,
            'is_webdev' => true,
            'is_pos_staff' => true,
            'created_at' => '2026-01-18',
            'active' => true,
        ],
        [
            'name' => 'System Administrator',
            'email' => 'admin@example.com',
            'password' => 'ChangeMe123!',
            'role' => 'super_admin',
            'is_admin' => true,
            'is_webdev' => true,
            'is_pos_staff' => false,
            'created_at' => '2026-01-18',
            'active' => true,
        ],
        [
            'name' => 'Farm Manager',
            'email' => 'manager@example.com',
            'password' => 'ChangeMe123!',
            'role' => 'admin',
            'is_admin' => true,
            'is_webdev' => false,
            'is_pos_staff' => false,
            'created_at' => '2026-01-18',
            'active' => true,
        ],
        [
            'name' => 'POS Staff',
            'email' => 'pos@example.com',
            'password' => 'ChangeMe123!',
            'role' => 'pos_staff',
            'is_admin' => false,
            'is_webdev' => false,
            'is_pos_staff' => true,
            'created_at' => '2026-01-18',
            'active' => true,
        ],
        [
            'name' => 'Juliette Milton',
            'email' => 'juliettemilton1@gmail.com',
            'password' => 'MillyMoo63',
            'role' => 'pos_staff',
            'is_admin' => false,
            'is_webdev' => false,
            'is_pos_staff' => true,
            'created_at' => '2025-12-09',
            'active' => true,
        ],
    
    
    
    
    
    
    
    
    
    ],

    /*
    |--------------------------------------------------------------------------
    | Session Configuration
    |--------------------------------------------------------------------------
    */
    'session_timeout' => 240, // minutes (4 hours)
    'remember_me' => true,
    'max_login_attempts' => 5,
    'lockout_duration' => 15, // minutes

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    */
    'require_2fa' => false,
    'log_all_access' => true,
    'allowed_ips' => [], // Empty array means all IPs allowed
];
