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
        'domain' => env( 'MAILGUN_DOMAIN' ),
        'secret' => env( 'MAILGUN_SECRET' ),
        'endpoint' => env( 'MAILGUN_ENDPOINT', 'api.mailgun.net' ),
    ],

    'postmark' => [
        'token' => env( 'POSTMARK_TOKEN' ),
    ],

    'ses' => [
        'key' => env( 'AWS_ACCESS_KEY_ID' ),
        'secret' => env( 'AWS_SECRET_ACCESS_KEY' ),
        'region' => env( 'AWS_DEFAULT_REGION', 'us-east-1' ),
    ],

    'translator' => [
        'endpoint' => env( 'TRANSLATOR_ENDPOINT' ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Zalo OA (Official Account)
    |--------------------------------------------------------------------------
    |
    | Cấu hình gửi thông báo cho khách qua Zalo OA: xác nhận đơn, trạng thái
    | món, hóa đơn và khuyến mãi. Không ghi access token thật vào file này;
    | đặt qua biến môi trường ZALO_OA_ACCESS_TOKEN (xem docs/zalo-oa.md).
    | Mặc định dry-run = true: chỉ ghi log, không gọi API thật.
    |
    */
    'zalo' => [
        'enabled' => env( 'ZALO_OA_ENABLED', false ),
        'dry_run' => env( 'ZALO_OA_DRY_RUN', true ),
        'oa_id' => env( 'ZALO_OA_ID' ),
        'access_token' => env( 'ZALO_OA_ACCESS_TOKEN' ),
        'secret' => env( 'ZALO_OA_SECRET' ),
        'api_base_url' => env( 'ZALO_OA_API_BASE_URL', 'https://openapi.zalo.me' ),
        'timeout' => env( 'ZALO_OA_TIMEOUT', 10 ),
    ],

    'ollama' => [
        'endpoint' => env( 'OLLAMA_ENDPOINT', 'http://127.0.0.1:11434' ),
        'model' => env( 'OLLAMA_MODEL' ),
        'timeout' => env( 'OLLAMA_TIMEOUT', 120 ),
    ],

];
