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
    ],
];
