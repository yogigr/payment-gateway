<?php

return [
    'default' => env('PAYMENT_GATEWAY', 'duitku'), // Pilih default gateway
    'callback_domain' => env('PAYMENT_CALLBACK_DOMAIN', ''),
    'return_domain' => env('PAYMENT_RETURN_DOMAIN', ''),
    'gateways' => [
        'duitku' => [
            'merchant_code' => env('DUITKU_MERCHANT_CODE'),
            'merchant_key' => env('DUITKU_MERCHANT_KEY'),
            'sandbox_mode' => env('DUITKU_SANDBOX_MODE', true),
            'sanitized_mode' => env('DUITKU_SANITIZED_MODE', false),
            'log' => env('DUITKU_LOGS', false),
            'payment_url' => env('DUITKU_PAYMENT_URL'),
        ],
        'finpay' => [
            'merchant_code' => env('FINPAY_MERCHANT_CODE'),
            'merchant_key' => env('FINPAY_MERCHANT_KEY'),
            'payment_url' => env('FINPAY_PAYMENT_URL'),
        ],
    ],
];