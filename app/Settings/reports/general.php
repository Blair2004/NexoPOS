<?php

use App\Services\Helper;

return [
    'label' =>  __( 'General' ),
    'fields'    =>  [
        [
            'type'  =>  'switch',
            'label' =>  __( 'Enable Email Reporting' ),
            'options'   =>  Helper::kvToJsOptions([
                'yes'   =>  __( 'Yes' ),
                'no'    =>  __( 'No' ),
            ]),
            'description'   =>  __( 'Determine if the reporting should be enabled globally.')
        ]
    ]
];