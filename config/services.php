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

    'telegram' => [
        'token'          => env('TELEGRAM_BOT_TOKEN'),
        'secret'         => env('TELEGRAM_WEBHOOK_SECRET'),
        'owner_chat_id'  => env('OWNER_CHAT_ID', ''),
    ],

    'gemini' => [
        'key'   => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),
    ],

    'notification' => [
        'channel' => env('NOTIFICATION_CHANNEL', 'telegram'),
    ],

    'whatsapp' => [
        'access_token'         => env('WHATSAPP_ACCESS_TOKEN'),
        'phone_number_id'      => env('WHATSAPP_PHONE_NUMBER_ID'),
        'recipient_phone'      => env('WHATSAPP_RECIPIENT_PHONE'),
        'webhook_verify_token' => env('WHATSAPP_WEBHOOK_VERIFY_TOKEN', 'catetin_verify_token'),
        'app_secret'           => env('WHATSAPP_APP_SECRET'),
    ],

];
