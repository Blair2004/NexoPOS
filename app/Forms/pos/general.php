<?php

use App\Services\Helper;

return [
    'label' => __( 'General Shipping' ),
    'fields' => [
        [
            'type' => 'select',
            'name' => 'shipping_type',
            'label' => __( 'Shipping Type' ),
            'options' => Helper::kvToJsOptions( [
                'flat' => __( 'Flat' ),
            ] ),
            'description' => __( 'Define how the shipping is calculated.' ),
        ], [
            'type' => 'number',
            'label' => __( 'Shipping Fees' ),
            'name' => 'shipping',
            'description' => __( 'Define shipping fees.' ),
        ],
    ],
];
