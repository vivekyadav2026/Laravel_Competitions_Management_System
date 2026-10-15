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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'hspsms' => [
        'api_url' => env('HSPSMS_API_URL', 'http://sms.hspsms.com/sendSMS'),
        'username' => env('HSPSMS_USERNAME', 'fsclub'),
        'apikey' => env('HSPSMS_API_KEY', 'f85eb1ba-485e-44c6-9002-8f7948abb803'),
        'sendername' => env('HSPSMS_SENDER_NAME', 'DPCORN'),
        'smstype' => env('HSPSMS_SMS_TYPE', 'TRANS'),
        'message_template' => env('HSPSMS_MESSAGE_TEMPLATE', '{otp} is Your One Time Password(OTP) for DEEP CORNER.'),
        'otp_length' => (int) env('OTP_LENGTH', 6),
        'otp_expiry_minutes' => (int) env('OTP_EXPIRY_MINUTES', 10),
        'resend_cooldown_seconds' => (int) env('OTP_RESEND_COOLDOWN', 60),
    ],


];
