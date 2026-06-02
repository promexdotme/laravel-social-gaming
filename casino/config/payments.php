<?php

return [
    'default_currency' => env('APP_CURRENCY', 'USD'),

    'drivers' => [
        'btcpay' => [
            'enabled' => env('BTCPAY_ENABLED', false),
            'host' => env('BTCPAY_HOST', ''), // e.g. https://btcpay.yourdomain.com
            'store_id' => env('BTCPAY_STORE_ID', ''),
            'api_key' => env('BTCPAY_API_KEY', ''),
            'webhook_secret' => env('BTCPAY_WEBHOOK_SECRET', ''),
            'webhook_route' => '/payment/webhook/btcpay',
        ],
        'stripe' => [
            'enabled' => env('STRIPE_ENABLED', false),
            'secret_key' => env('STRIPE_SECRET_KEY', ''),
            'public_key' => env('STRIPE_PUBLIC_KEY', ''),
            'webhook_secret' => env('STRIPE_WEBHOOK_SECRET', ''),
        ],
        'paypal' => [
            'enabled' => env('PAYPAL_ENABLED', false),
            'client_id' => env('PAYPAL_CLIENT_ID', ''),
            'secret' => env('PAYPAL_SECRET', ''),
            'mode' => env('PAYPAL_MODE', 'sandbox'), // sandbox or live
        ],
        'manual' => [
            'enabled' => env('MANUAL_PAYMENT_ENABLED', false),
            'instructions' => env('MANUAL_PAYMENT_INSTRUCTIONS', "Please send money to our Bank Account:\nIBAN: US1234567890\nBank Name: Sports Bank"),
        ],
    ],
];
