<?php

use App\Services\Helper;

return [
    'label' =>  __( 'Products' ),
    'fields'    =>  [
        [
            'type'          =>  'switch',
            'name'          =>  'ns_notifications_products_stock_enabled',
            'label'         =>  __( 'Low Stock products' ),
            'options'       =>  Helper::kvToJsOptions([ __( 'No' ), __( 'Yes' ) ]),
            'value'         =>  intval( $options->get( 'ns_notifications_products_stock_enabled' ) ),
            'description'   =>  __( 'Define if notification should be enabled on low stock products' )
        ], [
            'type'          =>  'multiselect',
            'name'          =>  'ns_notifications_products_stock_channel',
            'label'         =>  __( 'Low Stock Channel' ),
            'options'       =>  Helper::kvToJsOptions([
                'sms'       =>  __( 'SMS' ),
                'email'     =>  __( 'Email' ),
            ]),
            'value'         =>  $options->get( 'ns_notifications_products_stock_channel' ),
            'description'   =>  __( 'Define the notification channel for the low stock products.' )
        ], [
            'type'          =>  'switch',
            'name'          =>  'ns_notifications_products_expired_enabled',
            'label'         =>  __( 'Expired products' ),
            'options'       =>  Helper::kvToJsOptions([ __( 'No' ), __( 'Yes' ) ]),
            'value'         =>  intval( $options->get( 'ns_notifications_products_expired_enabled' ) ),
            'description'   =>  __( 'Define if notification should be enabled on expired products' )
        ], [
            'type'          =>  'multiselect',
            'name'          =>  'ns_notifications_products_expired_channel',
            'label'         =>  __( 'Expired Channel' ),
            'options'       =>  Helper::kvToJsOptions([
                'sms'       =>  __( 'SMS' ),
                'email'     =>  __( 'Email' ),
            ]),
            'value'         =>  $options->get( 'ns_notifications_products_expired_channel' ),
            'description'   =>  __( 'Define the notification channel for the expired products.' )
        ]
    ]
];