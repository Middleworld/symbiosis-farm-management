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
        'humanityawakeningproject@hotmail.com' => 'humanityawakeningproject@hotmail.com', // Jonathan uses same email for both
        // Add more mappings here if needed
    ],

    'users' => [
        [
            'name' => 'Martin',
            'email' => 'martin@middleworldfarms.org',
            'password' => 'AOhbM$4POkFEo)dN%eyZVzqd',
            'role' => 'super_admin',
            'is_admin' => true,
            'is_webdev' => true,
            'is_pos_staff' => false,
            'created_at' => '2025-06-09',
            'active' => true,
        ],
        [
            'name' => 'MWF Admin',
            'email' => 'admin@middleworldfarms.org',
            'password' => 'MWF2025Admin!',
            'role' => 'admin',
            'is_admin' => true,
            'is_webdev' => false,
            'is_pos_staff' => false,
            'created_at' => '2025-06-09',
            'active' => true,
        ],
        [
            'name' => 'Jonathan',
            'email' => 'humanityawakeningproject@hotmail.com',
            'password' => 'Gump7h21',
            'role' => 'super_admin',
            'is_admin' => true,
            'is_webdev' => true,
            'is_pos_staff' => false,
            'created_at' => '2025-08-18',
            'active' => true,
        ],
        [
            'name' => 'POS Staff 1',
            'email' => 'pos1@middleworldfarms.org',
            'password' => 'POS2025Staff!',
            'role' => 'pos_staff',
            'is_admin' => false,
            'is_webdev' => false,
            'is_pos_staff' => true,
            'created_at' => '2025-11-01',
            'active' => true,
        ],
        [
            'name' => 'POS Staff 2',
            'email' => 'pos2@middleworldfarms.org',
            'password' => 'POS2025Staff!',
            'role' => 'pos_staff',
            'is_admin' => false,
            'is_webdev' => false,
            'is_pos_staff' => true,
            'created_at' => '2025-11-01',
            'active' => true,
        ],
        [
            'name' => 'POS Manager',
            'email' => 'posmanager@middleworldfarms.org',
            'password' => 'POS2025Manager!',
            'role' => 'pos_manager',
            'is_admin' => false,
            'is_webdev' => false,
            'is_pos_staff' => true,
            'created_at' => '2025-11-01',
            'active' => true,
        ],
        [
            'name' => 'Katie Finn',
            'email' => 'Katie@middleworldfarms.org',
            'password' => 'CaravanMW2',
            'role' => 'admin',
            'is_admin' => false,
            'is_webdev' => false,
            'is_pos_staff' => true,
            'created_at' => '2025-11-27',
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
