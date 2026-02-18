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
'sns' => [
    'key' => env('AWS_SMS_ACCESS_KEY_ID'),
    'secret' => env('AWS_SMS_SECRET_ACCESS_KEY'),
    'region' => env('AWS_SMS_DEFAULT_REGION', 'us-east-2'),
    'sms_from' => env('AWS_SNS_SMS_FROM'), // Ensure this is in your .env file
],

    'zebra' => [
    'ip' => env('ZEBRA_PRINTER_IP', '192.168.1.60'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
