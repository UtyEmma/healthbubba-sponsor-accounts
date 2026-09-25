<?php

return [
    'default' => env('PAYMENT_GATEWAY', 'paystack'),

    'currency' => env('PAYMENT_CURRENCY', 'NGN'),

    'wallet' => [
        'min_amount' => env('PAYMENT_WALLET_MIN_AMOUNT', '100.00'),
        'max_amount' => env('PAYMENT_WALLET_MAX_AMOUNT', '10000000.00'),
    ],

    'gateways' => [
        'paystack' => [
            'public_key' => env('PAYSTACK_PUBLIC_KEY'),
            'secret_key' => env('PAYSTACK_SECRET_KEY'),
            'base_url' => env('PAYSTACK_BASE_URL', 'https://api.paystack.co'),
            'timeout' => (int) env('PAYSTACK_TIMEOUT', 10),
            'connect_timeout' => (int) env('PAYSTACK_CONNECT_TIMEOUT', 3),
            'verify_attempts' => (int) env('PAYSTACK_VERIFY_ATTEMPTS', 3),
            'retry_delay' => (int) env('PAYSTACK_RETRY_DELAY', 200),
        ],
        'stripe' => [
            'secret_key' => env('STRIPE_SECRET_KEY'),
            'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
            'api_version' => env('STRIPE_API_VERSION'),
            'timeout' => (int) env('STRIPE_TIMEOUT', 10),
            'connect_timeout' => (int) env('STRIPE_CONNECT_TIMEOUT', 3),
            'max_network_retries' => (int) env('STRIPE_MAX_NETWORK_RETRIES', 2),
        ],
    ],
];
