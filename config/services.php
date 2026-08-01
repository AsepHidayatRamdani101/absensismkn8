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

    'fonnte' => [
        'enabled' => env('FONNTE_ENABLED', false),
        'token' => env('FONNTE_TOKEN'),
        'api_url' => env('FONNTE_API_URL', 'https://api.fonnte.com/send'),
        'default_country_code' => env('FONNTE_DEFAULT_COUNTRY_CODE', '62'),
        'test_batch_delay_ms' => env('FONNTE_TEST_BATCH_DELAY_MS', 1200),
        'reminder_enabled' => env('FONNTE_REMINDER_ENABLED', false),
        'reminder_time' => env('FONNTE_REMINDER_TIME', '14:00'),
        'school_name' => env('FONNTE_SCHOOL_NAME', 'SMKN 8 GARUT'),
    ],

];
