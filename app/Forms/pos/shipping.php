<?php

use App\Crud\CustomerCrud;
use App\Services\Helper;

return [
    'label' => __( 'Shipping Address' ),
    'fields' => [
        [
            'type' => 'switch',
            'name' => '_use_customer_shipping',
            'label' => __( 'Use Customer Shipping' ),
            'options' => Helper::kvToJsOptions( [ __( 'No' ), __( 'Yes' ) ] ),
            'description' => __( 'Define whether the customer shipping information should be used.' ),
        ],
        ...( new CustomerCrud )->getForm()[ 'tabs' ][ 'shipping' ][ 'fields' ],
    ],
];
