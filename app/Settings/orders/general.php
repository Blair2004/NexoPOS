<?php

use App\Models\ExpenseCategory;
use App\Services\Helper;

return [
    'label'     =>  __( 'General' ),
    'fields'    =>  [
        [
            'type'  =>  'select',
            'label'     =>  __( 'Order Code Type' ),
            'description'   =>  __( 'Determine how the system will generate code for each orders.' ),
            'name'  =>  'ns_orders_code_type',
            'value'     =>  $options->get( 'ns_orders_code_type' ),
            'options'   =>  Helper::kvToJsOptions([
                'date_sequential'   =>  __( 'Sequential' ),
                'random_code'       =>  __( 'Random Code' ),
                'number_sequential' =>  __( 'Number Sequential' ),
            ])
        ], [
            'type'  =>  'switch',
            'label'     =>  __( 'Allow Unpaid Orders' ),
            'name'  =>  'ns_orders_allow_unpaid',
            'value'     =>  $options->get( 'ns_orders_allow_unpaid' ),
            'description'   =>  __( 'Will prevent incomplete orders to be placed. If credit is allowed, this option should be set to "yes".' ),
            'options'   =>  Helper::kvToJsOptions([
                'yes'   =>  __( 'Yes' ),
                'no'       =>  __( 'No' ),
            ])
        ], [
            'type'  =>  'switch',
            'label'     =>  __( 'Allow Partial Orders' ),
            'name'  =>  'ns_orders_allow_partial',
            'value'     =>  $options->get( 'ns_orders_allow_partial' ),
            'description'   =>  __( 'Will prevent partially paid orders to be placed.' ),
            'options'   =>  Helper::kvToJsOptions([
                'yes'   =>  __( 'Yes' ),
                'no'       =>  __( 'No' ),
            ])
        ], [
            'type'  =>  'select',
            'label'     =>  __( 'Quotation Expiration' ),
            'name'  =>  'ns_orders_quotation_expiration',
            'value'     =>  $options->get( 'ns_orders_quotation_expiration' ),
            'description'   =>  __( 'Quotations will get deleted after they defined they has reached.' ),
            'options'   =>  Helper::kvToJsOptions( collect([3,5,10,15,30])->mapWithKeys( function( $days ) {
                return [
                    $days  =>  sprintf( __( '%s Days' ), $days )
                ];
            }))
        ], [
            'type'      =>  'select',
            'label'     =>  __( 'Orders Follow Up' ),
            'name'      =>  'ns_orders_follow_up',
            'value'     =>  $options->get( 'ns_orders_follow_up' ),
            'description'   =>  __( 'Quotations will get deleted after they defined they has reached.' ),
            'options'   =>  Helper::kvToJsOptions([
                'yes'   =>  __( 'Yes' ),
                'no'    =>  __( 'No' )
            ])
        ]
    ]
];