<?php

use App\Services\Helper;

return [
    'label' => __( 'General' ),
    'fields' => [
        [
            'type' => 'switch',
            'name' => 'ns_reports_email',
            'label' => __( 'Enable Email Reporting' ),
            'options' => Helper::kvToJsOptions( [
                'yes' => __( 'Yes' ),
                'no' => __( 'No' ),
            ] ),
            'value' => ns()->option->get( 'ns_reports_email' ),
            'description' => __( 'Determine if the reporting should be enabled globally.' ),
        ],
    ],
];
