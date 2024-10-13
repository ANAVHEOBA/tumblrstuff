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

    'tumblr' => [
        'client_id' => env('TUMBLR_CLIENT_ID'),
        'client_secret' => env('TUMBLR_CLIENT_SECRET'),
        'redirect' => env('TUMBLR_REDIRECT_URI'),
    ],


    'weibo' => [
        'app_key' => env('WEIBO_APP_KEY'),
        'app_secret' => env('WEIBO_APP_SECRET'),
        'callback_url' => env('WEIBO_CALLBACK_URL'),
    ],

    'wechat' => [
    'appid' => env('WECHAT_APPID'),
    'secret' => env('WECHAT_SECRET'),
],


'douyin' => [
        'client_key' => env('DOUYIN_CLIENT_KEY'),
        'client_secret' => env('DOUYIN_CLIENT_SECRET'),
        'redirect' => env('DOUYIN_REDIRECT_URI'),
    ],

    'baidu' => [
    'client_id' => env('BAIDU_CLIENT_ID'),
    'client_secret' => env('BAIDU_CLIENT_SECRET'),
    'redirect' => env('BAIDU_REDIRECT_URI'),
],

'meetup' => [
    'client_id' => env('MEETUP_CLIENT_ID'),
    'client_secret' => env('MEETUP_CLIENT_SECRET'),
],

];
