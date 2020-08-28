<?php

use App\Services\Helper;

return [
    'label' =>  __( 'General' ),
    'fields'    =>  [
        [
            'type'  =>  'switch',
            'options'       =>  Helper::kvToJsOptions([
                'yes'       =>  __( 'Yes' ),
                'no'        =>  __( 'No' )
            ]),
            'label'         =>  __( 'Enable The Multistore Mode' ),
            'value'         =>  $options->get( 'ns_store_multistore_enabled', 'no' ),
            'name'          =>  'ns_store_multistore_enabled',
            'description'   =>  __( 'Will enable the multistore.' )
        ]
    ]
];