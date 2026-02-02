<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Tripay Mode
    |--------------------------------------------------------------------------
    |
    | Set to 'sandbox' for testing or 'production' for live transactions.
    |
    */
    'mode' => env('TRIPAY_MODE', 'sandbox'),

    /*
    |--------------------------------------------------------------------------
    | Merchant Code
    |--------------------------------------------------------------------------
    |
    | Your Tripay merchant code.
    |
    */
    'merchant_code' => env('TRIPAY_MERCHANT_CODE', ''),

    /*
    |--------------------------------------------------------------------------
    | API Key
    |--------------------------------------------------------------------------
    |
    | Your Tripay API key.
    |
    */
    'api_key' => env('TRIPAY_API_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Private Key
    |--------------------------------------------------------------------------
    |
    | Your Tripay private key for generating signatures.
    |
    */
    'private_key' => env('TRIPAY_PRIVATE_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | API URLs
    |--------------------------------------------------------------------------
    |
    | Base URLs for Tripay API.
    |
    */
    'sandbox_url' => env('TRIPAY_SANDBOX_URL', 'https://tripay.co.id/api-sandbox'),
    'production_url' => env('TRIPAY_PRODUCTION_URL', 'https://tripay.co.id/api'),
];
