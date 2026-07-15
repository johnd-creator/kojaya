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

    'fcm' => [
        'server_key' => env('FCM_SERVER_KEY'),
        'endpoint' => env('FCM_ENDPOINT', 'https://fcm.googleapis.com/fcm/send'),
    ],

    'midtrans' => [
        'merchant_id' => env('MIDTRANS_MERCHANT_ID'),
        'server_key' => env('MIDTRANS_SERVER_KEY'),
        'client_key' => env('MIDTRANS_CLIENT_KEY'),
        'is_production' => env('MIDTRANS_IS_PRODUCTION', false),
        'va_bank' => env('MIDTRANS_VA_BANK', 'permata'),
        'qris_acquirer' => env('MIDTRANS_QRIS_ACQUIRER', 'gopay'),
    ],

    'whatsapp' => [
        'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'endpoint' => env('WHATSAPP_ENDPOINT', 'https://graph.facebook.com/v20.0'),
        'default_country_code' => env('WHATSAPP_DEFAULT_COUNTRY_CODE', '62'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', env('APP_URL').'/auth/google/callback'),
        'sso_enabled' => env('GOOGLE_SSO_ENABLED', false),
        'auto_link_existing_members' => env('GOOGLE_SSO_AUTO_LINK_EXISTING_MEMBERS', false),
        'allow_new_member_registration' => env('GOOGLE_SSO_ALLOW_NEW_MEMBER_REGISTRATION', true),
        'hosted_domains' => array_filter(array_map('trim', explode(',', (string) env('GOOGLE_SSO_HOSTED_DOMAINS', '')))),
        'guzzle' => [
            'connect_timeout' => (float) env('GOOGLE_SSO_CONNECT_TIMEOUT', 5),
            'timeout' => (float) env('GOOGLE_SSO_TIMEOUT', 15),
        ],
    ],

    'auth_sso' => [
        'enabled' => env('GOOGLE_SSO_ENABLED', false),
        'state_ttl' => (int) env('GOOGLE_SSO_STATE_TTL', 300),
    ],

];
