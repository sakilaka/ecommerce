<?php

return [
    'sslcommerz' => [
        'store_id' => env('SSLCOMMERZ_STORE_ID'),
        'store_password' => env('SSLCOMMERZ_STORE_PASSWORD'),
        'sandbox' => env('SSLCOMMERZ_SANDBOX', true),
        'init_url' => env('SSLCOMMERZ_INIT_URL', 'https://sandbox.sslcommerz.com/gwprocess/v4/api.php'),
        'validation_url' => env('SSLCOMMERZ_VALIDATION_URL', 'https://sandbox.sslcommerz.com/validator/api/validationserverAPI.php'),
        'success_url' => env('SSLCOMMERZ_SUCCESS_URL', '/api/storefront/payments/sslcommerz/success'),
        'fail_url' => env('SSLCOMMERZ_FAIL_URL', '/api/storefront/payments/sslcommerz/fail'),
        'cancel_url' => env('SSLCOMMERZ_CANCEL_URL', '/api/storefront/payments/sslcommerz/cancel'),
        'ipn_url' => env('SSLCOMMERZ_IPN_URL', '/api/storefront/payments/sslcommerz/ipn'),
        'connect_timeout' => env('SSLCOMMERZ_CONNECT_TIMEOUT', 5),
        'timeout' => env('SSLCOMMERZ_TIMEOUT', 15),
    ],
];
