<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    'flexpay' => [
        'merchant' => env('FLEXPAY_MARCHAND'),
        'token' => env('FLEXPAY_API_TOKEN'),
        'gateway_mobile' => env('FLEXPAY_GATEWAY_MOBILE', 'https://backend.flexpay.cd/api/rest/v1/paymentService'),
        'gateway_card' => env('FLEXPAY_GATEWAY_CARD', 'https://cardpayment.flexpay.cd/v1.1/pay'),
        'gateway_check' => env('FLEXPAY_GATEWAY_CHECK', 'https://backend.flexpay.cd/api/rest/v1/check'),
    ],

    'keccel' => [
        'token' => env('KECCEL_SMS_TOKEN'),
        'sender' => env('KECCEL_SMS_FROM', 'LIALALIONNE'),
        'gateway' => env('KECCEL_SMS_GATEWAY', 'https://api.keccel.com/sms/v2/message.asp'),
    ],

];
