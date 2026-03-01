<?php

return [
    /*
    |--------------------------------------------------------------------------
    | PayTheFly Pro Configuration
    |--------------------------------------------------------------------------
    |
    | These values can also be managed from the NexoPOS admin panel under
    | Modules → PayTheFly Settings. Database options take precedence.
    |
    */

    'project_id'         => env( 'PAYTHEFLY_PROJECT_ID', '' ),
    'project_key'        => env( 'PAYTHEFLY_PROJECT_KEY', '' ),
    'private_key'        => env( 'PAYTHEFLY_PRIVATE_KEY', '' ),
    'chain'              => env( 'PAYTHEFLY_CHAIN', 'BSC' ),
    'token_address'      => env( 'PAYTHEFLY_TOKEN_ADDRESS', '' ),
    'verifying_contract' => env( 'PAYTHEFLY_VERIFYING_CONTRACT', '' ),
    'deadline_minutes'   => env( 'PAYTHEFLY_DEADLINE_MINUTES', 30 ),

    /*
    |--------------------------------------------------------------------------
    | Webhook
    |--------------------------------------------------------------------------
    |
    | Maximum age (in seconds) for webhook timestamp validation.
    | Default: 300 (5 minutes)
    |
    */
    'webhook_max_age' => env( 'PAYTHEFLY_WEBHOOK_MAX_AGE', 300 ),
];
