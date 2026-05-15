<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
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

    'payment_api' => [
        'base_url' => env('PAYMENT_API_BASE_URL'),
        'api_key' => env('PAYMENT_API_KEY'),
        'provider' => env('PAYMENT_API_PROVIDER', 'mock'),
        'webhook_secret' => env('PAYMENT_API_WEBHOOK_SECRET'),
        'webhook_signature_header' => env('PAYMENT_API_WEBHOOK_SIGNATURE_HEADER', 'X-Webhook-Signature'),
        'timeout' => (int) env('PAYMENT_API_TIMEOUT', 10),
        'webhook_url' => env('PAYMENT_API_WEBHOOK_URL'),
    ],

];
