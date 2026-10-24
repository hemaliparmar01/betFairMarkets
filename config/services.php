<?php

return [
    'paypal' => [
        'base_url' => env('PAYPAL_BASE_URL', env('PAYPAL_MODE', 'sandbox') === 'live' ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com'),
        'client_id' => env('PAYPAL_CLIENT_ID'),
        'secret' => env('PAYPAL_SECRET'),
        'webhook_id' => env('PAYPAL_WEBHOOK_ID'),
    ],
    'bog' => [
        'base_url' => env('BOG_BASE_URL', 'https://api.bog.ge'),
        'auth_url' => env('BOG_AUTH_URL', 'https://oauth2.bog.ge/auth/realms/bog/protocol/openid-connect/token'),
        'client_id' => env('BOG_CLIENT_ID'),
        'secret' => env('BOG_CLIENT_SECRET'),
        'public_key' => env('BOG_CALLBACK_PUBLIC_KEY'),
        'recurring_enabled' => env('BOG_RECURRING_ENABLED', false),
    ],
    'flitt' => [
        'base_url' => env('FLITT_BASE_URL', 'https://pay.flitt.com'),
        'merchant_id' => env('FLITT_MERCHANT_ID'),
        'key' => env('FLITT_PAYMENT_KEY'),
        'currency' => env('FLITT_CURRENCY', 'EUR'),
        'recurring_enabled' => env('FLITT_RECURRING_ENABLED', false),
        'cycles' => (int) env('FLITT_SUBSCRIPTION_CYCLES', 120),
    ],

    'infobip' => [
        'base_url' => env('INFOBIP_BASE_URL'),
        'key' => env('INFOBIP_API_KEY'),
        'sender' => env('INFOBIP_SENDER', 'BFMarkets'),
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

    // Conservative retention; recovery uses existing provider reconciliation only.
    'match_lifecycle' => [
        'retention_seconds' => (int) env('MATCH_BACKUP_RETENTION_SECONDS', 7 * 86400),
        'race_retention_seconds' => (int) env('RACE_BACKUP_RETENTION_SECONDS', 86400),
        'resume_stale_seconds' => (int) env('MATCH_RESUME_STALE_SECONDS', 300),
        'backup_recheck_seconds' => (int) env('MATCH_BACKUP_RECHECK_SECONDS', 3600),
        'cleanup_checks' => (int) env('MATCH_BACKUP_CLEANUP_CHECKS', 3),
        'tombstone_seconds' => (int) env('MATCH_BACKUP_TOMBSTONE_SECONDS', 30 * 86400),
        'tennis_stale_seconds' => (int) env('TENNIS_PROVIDER_STALE_SECONDS', 6 * 3600),
        'cricket_stale_seconds' => (int) env('CRICKET_PROVIDER_STALE_SECONDS', 3 * 86400),
    ],

    'fixtures' => [
        'goal_highlight_seconds' => (int) env('FIXTURE_GOAL_HIGHLIGHT_SECONDS', 40),
    ],

];
