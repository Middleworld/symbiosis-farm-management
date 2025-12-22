<?php

return [
    /*
    |--------------------------------------------------------------------------
    | POS Payment Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for various payment methods and card readers supported
    | in the POS system.
    |
    */

    'payment_processors' => [
        'stripe' => [
            'enabled' => env('STRIPE_POS_ENABLED', true),
            'secret_key' => env('STRIPE_SECRET'),
            'publishable_key' => env('STRIPE_KEY'),
            'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
            'terminal_location' => env('STRIPE_TERMINAL_LOCATION'),
        ],

        'square' => [
            'enabled' => env('SQUARE_POS_ENABLED', false),
            'application_id' => env('SQUARE_APPLICATION_ID'),
            'access_token' => env('SQUARE_ACCESS_TOKEN'),
            'location_id' => env('SQUARE_LOCATION_ID'),
        ],

        'sumup' => [
            'enabled' => env('SUMUP_POS_ENABLED', false),
            'client_id' => env('SUMUP_CLIENT_ID'),
            'client_secret' => env('SUMUP_CLIENT_SECRET'),
            'merchant_code' => env('SUMUP_MERCHANT_CODE'),
        ],
    ],

    'card_readers' => [
        'bluetooth' => [
            'enabled' => env('BLUETOOTH_READERS_ENABLED', true),
            'supported_devices' => [
                'square_reader' => [
                    'name_prefix' => 'Square',
                    'service_uuid' => '00001101-0000-1000-8000-00805f9b34fb',
                ],
                'stripe_reader' => [
                    'name_prefix' => 'Stripe',
                    'service_uuid' => '00001101-0000-1000-8000-00805f9b34fb',
                ],
                'sumup_reader' => [
                    'name_prefix' => 'SumUp',
                    'service_uuid' => '00001101-0000-1000-8000-00805f9b34fb',
                ],
            ],
        ],

        'nfc' => [
            'enabled' => env('NFC_PAYMENTS_ENABLED', true),
            'supported_protocols' => ['NFC-A', 'NFC-B', 'NFC-F', 'NFC-V'],
            'timeout_seconds' => 30,
        ],

        'usb' => [
            'enabled' => env('USB_READERS_ENABLED', false),
            'auto_detect' => true,
        ],
    ],

    'payment_methods' => [
        'cash' => [
            'enabled' => true,
            'name' => 'Cash',
            'icon' => '💵',
            'requires_confirmation' => false,
        ],

        'card' => [
            'enabled' => true,
            'name' => 'Card (Chip & PIN)',
            'icon' => '💳',
            'requires_confirmation' => true,
            'processor' => 'stripe', // Default processor
        ],

        'contactless' => [
            'enabled' => true,
            'name' => 'Contactless (NFC)',
            'icon' => '📱',
            'requires_confirmation' => true,
            'processor' => 'stripe',
        ],

        'bluetooth_reader' => [
            'enabled' => true,
            'name' => 'Bluetooth Card Reader',
            'icon' => '🔗',
            'requires_confirmation' => true,
            'processor' => 'stripe',
        ],

        'square' => [
            'enabled' => env('SQUARE_POS_ENABLED', false),
            'name' => 'Square',
            'icon' => '⬜',
            'requires_confirmation' => true,
            'processor' => 'square',
        ],

        'sumup' => [
            'enabled' => env('SUMUP_POS_ENABLED', false),
            'name' => 'SumUp',
            'icon' => '💰',
            'requires_confirmation' => true,
            'processor' => 'sumup',
        ],
    ],

    'transaction_settings' => [
        'currency' => 'GBP',
        'currency_symbol' => '£',
        'tax_rate' => 0.20, // 20% VAT
        'surcharge_enabled' => false,
        'surcharge_percentage' => 0.015, // 1.5% for card payments
        'receipt_printing' => true,
        'email_receipts' => true,
    ],

    'security' => [
        'require_pin_for_large_transactions' => true,
        'large_transaction_threshold' => 100.00, // £100
        'max_transaction_amount' => 1000.00, // £1000
        'require_signature' => false,
        'signature_threshold' => 50.00, // £50
    ],

    'logging' => [
        'log_all_transactions' => true,
        'log_payment_attempts' => true,
        'log_card_reader_events' => true,
        'anonymize_card_data' => true,
    ],
];