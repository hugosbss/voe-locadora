<?php

return [

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

    'vca' => [
        'whatsapp' => env('VCA_WHATSAPP', '5579991379313'),
        'city' => env('VCA_CITY', 'Itabaianinha'),
        'price_from' => env('VCA_PRICE_FROM', '599,00'),
        'vehicle_year' => env('VCA_VEHICLE_YEAR', '2027'),
        'pre_launch_label' => env('VCA_PRE_LAUNCH_LABEL', 'Cotas promocionais — Pré lançamento'),
        'instagram' => env('VCA_INSTAGRAM', 'vcaclube'),
        'instagram_url' => env('VCA_INSTAGRAM_URL'),
    ],

];
