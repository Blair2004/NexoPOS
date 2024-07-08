<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Broadcaster
    |--------------------------------------------------------------------------
    |
    | This option controls the default broadcaster that will be used by the
    | framework when an event needs to be broadcast. You may set this to
    | any of the connections defined in the "connections" array below.
    |
    | Supported: "pusher", "redis", "log", "null"
    |
    */

    'default' => env( 'BROADCAST_DRIVER', 'null' ),

    /*
    |--------------------------------------------------------------------------
    | Broadcast Connections
    |--------------------------------------------------------------------------
    |
    | Here you may define all of the broadcast connections that will be used
    | to broadcast events to other systems or over websockets. Samples of
    | each available type of connection are provided inside this array.
    |
    */

    'connections' => [

        'reverb' => [
            'driver' => 'reverb',
            'key' => env( 'REVERB_APP_KEY' ),
            'secret' => env( 'REVERB_APP_SECRET' ),
            'app_id' => env( 'REVERB_APP_ID' ),
            'options' => [
                'host' => env( 'REVERB_HOST' ),
                'port' => env( 'REVERB_PORT', 443 ),
                'scheme' => env( 'REVERB_SCHEME', 'https' ),
                'useTLS' => env( 'REVERB_SCHEME', 'https' ) === 'https',
            ],
            'client_options' => [
                'verify' => false,
            ],
        ],

        'pusher' => [
            'driver' => 'pusher',
            'key' => env( 'PUSHER_APP_KEY' ),
            'secret' => env( 'PUSHER_APP_SECRET' ),
            'app_id' => env( 'PUSHER_APP_ID' ),
            'options' => [
                'cluster' => env( 'PUSHER_APP_CLUSTER' ),
                'useTLS' => env( 'NS_SOCKET_SECURED', false ) ? true : false,
                'host' => env( 'NS_SOCKET_DOMAIN', env( 'SESSION_DOMAIN' ) ),
                'port' => env( 'NS_SOCKET_PORT', 6001 ),
                'scheme' => env( 'NS_SOCKET_SECURED', false ) ? 'https' : 'http',
                'encrypted' => env( 'NS_SOCKET_SECURED', false ) ? true : false,
                'curl_options' => [
                    CURLOPT_SSL_VERIFYHOST => false,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_CAINFO => env( 'NS_SOCKET_CA', null ),
                ],
            ],
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
        ],

        'log' => [
            'driver' => 'log',
        ],

        'null' => [
            'driver' => 'null',
        ],

    ],

];
