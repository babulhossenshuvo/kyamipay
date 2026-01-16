<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Kyami Pay Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration file contains all the settings needed to integrate
    | with the Kyami Pay payment gateway API.
    |
    */

    // Base URL for API requests
    'base_url' => env('KPAY_BASE_URL', 'https://kyamiprint.kp'),

    // Sandbox URL for testing
    'sandbox_url' => env('KPAY_SANDBOX_URL', 'https://private-f32974-kyamirefapiv2.apiary-mock.com'),

    // Use sandbox mode
    'sandbox_mode' => env('KPAY_SANDBOX_MODE', true),

    // Entity code
    'entity' => env('KPAY_ENTITY', '0000'),

    // API Token for authentication
    'token' => env('KPAY_TOKEN', ''),

    // Hash for Sys-Marc-Zone header
    'hash' => env('KPAY_HASH', ''),

    // Factory Bag (Content-Type reference)
    'factory_bag' => env('KPAY_FACTORY_BAG', 'Content'),

    // Webhook configuration
    'webhook' => [
        'enabled' => env('KPAY_WEBHOOK_ENABLED', true),
        'url' => env('KPAY_WEBHOOK_URL', '/api/kpay/webhook'),
        'secret' => env('KPAY_WEBHOOK_SECRET', ''),
    ],

    // Currency configuration
    'currency' => env('KPAY_CURRENCY', 'AOA'),

    // Default reference expiry time (in hours)
    'reference_expiry_hours' => env('KPAY_REFERENCE_EXPIRY_HOURS', 24),

    // Timeout for API requests (in seconds)
    'timeout' => env('KPAY_TIMEOUT', 30),

    // Enable request logging
    'log_requests' => env('KPAY_LOG_REQUESTS', false),
];
