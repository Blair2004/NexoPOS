<?php

use App\Services\Helper;

return [
    'label' =>  __( 'General' ),
    'fields'    =>  [
        [
            'type'          =>  'switch',
            'label'         =>  __( 'Enable Workers' ),
            'description'   =>  __( 'Enable background services for NexoPOS 4.x' ),
            'name'          =>  'ns_workers_enabled',
            'options'       =>  Helper::kvToJsOptions([ __( 'No' ), __( 'Yes' ) ])
        ]
    ]
];