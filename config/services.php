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

    'bulk_sms_ng' => [
        'email' => env('BULK_SMS_NG_EMAIL'),
        'password' => env('BULK_SMS_NG_PASSWORD'),
        // Same Bulk-SMS.ng account as KLAES. The sender must be registered with the vendor (max 11 characters): an
        // unregistered one is billed and never delivered. Agency name by user choice (2026-11-23); KLAES-CONTROL goes out as KLAES-CONTR.
        'sender' => env('BULK_SMS_NG_SENDER', 'KLAES-METRO'),
        'gateway' => env('BULK_SMS_NG_GATEWAY', '1'),
        'allow_console' => env('BULK_SMS_NG_ALLOW_CONSOLE', false),
    ],

];
