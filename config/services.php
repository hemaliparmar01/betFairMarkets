<?php

return [

    'betfair' => [
        'chart_url' => env('BETFAIR_API_KEY'),
    ],

    'pinnodds' => [
        'key' => env('PINACLE_ODDS_KEY'),
        'url' => 'https://pinnodds.com',
    ],

    'sports_api_pro' => [
        'daily_rest_limit' => (int) env('SPORTS_API_PRO_DAILY_REST_LIMIT', 75000),
        'rest_reserve' => (int) env('SPORTS_API_PRO_REST_RESERVE', 7500),
        'football_v6_daily_budget' => (int) env('SPORTS_API_PRO_FOOTBALL_V6_DAILY_BUDGET', 30000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

];
