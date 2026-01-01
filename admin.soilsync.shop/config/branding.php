<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Branding Presets
    |--------------------------------------------------------------------------
    |
    | Pre-defined color schemes and themes that users can select from
    | to quickly apply professional branding to their farm management system.
    |
    */

    'presets' => [
        'default' => [
            'name' => 'Default (Farm Green)',
            'description' => 'Classic farm green theme with earthy tones',
            'colors' => [
                'primary' => '#2d5016',
                'secondary' => '#5a7c3e',
                'accent' => '#f5c518',
                'text' => '#1a1a1a',
                'sidebar_text' => '#ffffff',
                'background' => '#ffffff',
                'border' => '#dee2e6',
                'success' => '#28a745',
                'warning' => '#ffc107',
                'danger' => '#dc3545',
            ],
        ],

        'modern-blue' => [
            'name' => 'Modern Blue',
            'description' => 'Clean blue and white theme for modern farms',
            'colors' => [
                'primary' => '#0066cc',
                'secondary' => '#4da6ff',
                'accent' => '#ff6b35',
                'text' => '#1a1a1a',
                'sidebar_text' => '#ffffff',
                'background' => '#ffffff',
                'border' => '#e1e5e9',
                'success' => '#28a745',
                'warning' => '#ffc107',
                'danger' => '#dc3545',
            ],
        ],

        'earth-brown' => [
            'name' => 'Earth & Soil',
            'description' => 'Warm brown and earth tones for soil-focused farms',
            'colors' => [
                'primary' => '#8b4513',
                'secondary' => '#a0522d',
                'accent' => '#daa520',
                'text' => '#1a1a1a',
                'sidebar_text' => '#ffffff',
                'background' => '#fdf5e6',
                'border' => '#d2b48c',
                'success' => '#228b22',
                'warning' => '#daa520',
                'danger' => '#b22222',
            ],
        ],

        'organic-green' => [
            'name' => 'Organic Green',
            'description' => 'Fresh green theme for organic farming operations',
            'colors' => [
                'primary' => '#228b22',
                'secondary' => '#32cd32',
                'accent' => '#ffd700',
                'text' => '#1a1a1a',
                'sidebar_text' => '#ffffff',
                'background' => '#f0fff0',
                'border' => '#98fb98',
                'success' => '#006400',
                'warning' => '#daa520',
                'danger' => '#b22222',
            ],
        ],

        'corporate-gray' => [
            'name' => 'Corporate Gray',
            'description' => 'Professional gray and blue theme for corporate farms',
            'colors' => [
                'primary' => '#2c3e50',
                'secondary' => '#34495e',
                'accent' => '#3498db',
                'text' => '#1a1a1a',
                'sidebar_text' => '#ffffff',
                'background' => '#ffffff',
                'border' => '#bdc3c7',
                'success' => '#27ae60',
                'warning' => '#f39c12',
                'danger' => '#e74c3c',
            ],
        ],

        'sunset-orange' => [
            'name' => 'Sunset Orange',
            'description' => 'Warm orange and sunset colors for vibrant farms',
            'colors' => [
                'primary' => '#ff4500',
                'secondary' => '#ff6347',
                'accent' => '#ffd700',
                'text' => '#1a1a1a',
                'sidebar_text' => '#ffffff',
                'background' => '#fff8dc',
                'border' => '#ffa500',
                'success' => '#32cd32',
                'warning' => '#ff6347',
                'danger' => '#dc143c',
            ],
        ],

        'dark-theme' => [
            'name' => 'Dark Professional',
            'description' => 'Dark theme for night-time farm operations',
            'colors' => [
                'primary' => '#1a1a1a',
                'secondary' => '#2d2d2d',
                'accent' => '#4CAF50',
                'text' => '#ffffff',
                'sidebar_text' => '#ffffff',
                'background' => '#121212',
                'border' => '#333333',
                'success' => '#4CAF50',
                'warning' => '#ff9800',
                'danger' => '#f44336',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Font Presets
    |--------------------------------------------------------------------------
    |
    | Pre-defined font combinations for different branding styles
    |
    */

    'fonts' => [
        'modern' => [
            'name' => 'Modern Sans',
            'heading' => 'Inter, system-ui, sans-serif',
            'body' => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
        ],

        'classic' => [
            'name' => 'Classic Serif',
            'heading' => 'Georgia, "Times New Roman", serif',
            'body' => '"Times New Roman", serif',
        ],

        'farm' => [
            'name' => 'Farm Friendly',
            'heading' => 'Arial, sans-serif',
            'body' => 'Arial, sans-serif',
        ],

        'tech' => [
            'name' => 'Tech Modern',
            'heading' => '"SF Pro Display", "Segoe UI", sans-serif',
            'body' => '"SF Pro Text", "Segoe UI", sans-serif',
        ],
    ],
];