<?php

use App\Services\Helper;

return [
    'label' => __( 'General' ),
    'fields' => [
        [
            'type' => 'switch',
            'label' => __( 'Enable Workers' ),
            'description' => __( 'Enable background services for NexoPOS. Refresh to check whether the option has turned to "Yes".' ),
            'name' => 'ns_workers_enabled',
            'value' => ns()->option->get( 'ns_workers_enabled', 'no' ),
            'options' => collect( Helper::kvToJsOptions( [
                'no' => __( 'No' ),
                'await_confirm' => __( 'Test' ),
                'yes' => __( 'Yes' ),
            ] ) )->map( function ( $option ) {
                $option[ 'disabled' ] = false;
                if ( $option[ 'value' ] === 'yes' ) {
                    $option[ 'disabled' ] = true;
                }

                return $option;
            } ),
        ],
    ],
];
