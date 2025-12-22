<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Open Banking Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Open Banking PSD2 integration
    |
    */

    'environment' => env('OPENBANKING_ENVIRONMENT', 'sandbox'),
    
    'organization_id' => env('OPENBANKING_ORGANIZATION_ID'),
    
    'client_id' => env('OPENBANKING_CLIENT_ID'),
    
    'software_id' => env('OPENBANKING_SOFTWARE_ID'),
    
    'key_id' => env('OPENBANKING_KEY_ID'),
    
    'certificate_path' => env('OPENBANKING_CERTIFICATE_PATH'),
    
    'private_key_path' => env('OPENBANKING_PRIVATE_KEY_PATH'),
    
    'key_passphrase' => env('OPENBANKING_KEY_PASSPHRASE'),
    
    'ssa' => env('OPENBANKING_SSA'),
    
    'redirect_uri' => env('OPENBANKING_REDIRECT_URI'),
    
    'jwks_endpoint' => env('OPENBANKING_JWKS_ENDPOINT'),
];
