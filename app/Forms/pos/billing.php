<?php

use App\Crud\CustomerCrud;
use App\Services\Helper;

return [
    'label' => __( 'Billing Address' ),
    'fields' => [
        [
            'type' => 'switch',
            'name' => '_use_customer_billing',
            'label' => __( 'Use Customer Billing' ),
            'options' => Helper::kvToJsOptions( [ __( 'No' ), __( 'Yes' ) ] ),
            'description' => __( 'Define whether the customer billing information should be used.' ),
        ],
        ...( new CustomerCrud )->getForm()[ 'tabs' ][ 'billing' ][ 'fields' ],
    ],
];
