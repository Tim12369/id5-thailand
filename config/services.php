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

    'line' => [
        'channel_id' => env('LINE_CHANNEL_ID'),
        'channel_secret' => env('LINE_CHANNEL_SECRET'),
        'channel_access_token' => env('LINE_CHANNEL_ACCESS_TOKEN'),
        'default_locale' => env('LINE_DEFAULT_LOCALE', 'th'),
        'session_timeout_minutes' => env('LINE_SESSION_TIMEOUT_MINUTES', 30),
    ],

    'google_maps' => [
        'api_key' => env('GOOGLE_MAPS_API_KEY'),
    ],

    'groq' => [
        'key'        => env('GROQ_API_KEY'),
        'model'      => env('GROQ_MODEL', 'llama-3.1-8b-instant'),
        'max_tokens' => (int) env('GROQ_MAX_TOKENS', 1024),
    ],

];
