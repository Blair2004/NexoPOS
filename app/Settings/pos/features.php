<?php

use App\Services\Helper;

return [
    'label' =>  __( 'Features' ),
    'fields'    =>  [
        [
            'name'              =>  'ns_pos_sound_enabled',
            'value'             =>  $options->get( 'ns_pos_sound_enabled' ),
            'label'             =>  __( 'Sound Effect' ), 
            'type'              =>  'switch',
            'options'           =>  Helper::kvToJsOptions([
                'yes'           =>  __( 'Yes' ),
                'no'            =>  __( 'No' )
            ]),
            'description'       =>  __( 'Enable sound effect on the POS.' ),
        ], [
            'name'              =>  'ns_pos_customers_creation_enabled',
            'value'             =>  $options->get( 'ns_pos_customers_creation_enabled' ),
            'label'             =>  __( 'Allow Customer Creation' ), 
            'type'              =>  'switch',
            'options'           =>  Helper::kvToJsOptions([
                'yes'           =>  __( 'Yes' ),
                'no'            =>  __( 'No' )
            ]),
            'description'       =>  __( 'Allow customers to be created on the POS.' ),
        ], [
            'name'              =>  'ns_pos_quick_product',
            'value'             =>  $options->get( 'ns_pos_quick_product' ),
            'label'             =>  __( 'Quick Product' ), 
            'type'              =>  'switch',
            'options'           =>  Helper::kvToJsOptions([
                'yes'           =>  __( 'Yes' ),
                'no'            =>  __( 'No' )
            ]),
            'description'       =>  __( 'Allow quick product to be created from the POS.' ),
        ], [
            'name'              =>  'ns_pos_unit_price_ediable',
            'value'             =>  $options->get( 'ns_pos_unit_price_ediable' ),
            'label'             =>  __( 'Editable Unit Price' ), 
            'type'              =>  'switch',
            'options'           =>  Helper::kvToJsOptions([
                'yes'           =>  __( 'Yes' ),
                'no'            =>  __( 'No' )
            ]),
            'description'       =>  __( 'Allow product unit price to be edited.' ),
        ], [
            'name'              =>  'ns_pos_gross_price_used',
            'value'             =>  $options->get( 'ns_pos_gross_price_used' ),
            'label'             =>  __( 'Use Gross Prices' ), 
            'type'              =>  'switch',
            'options'           =>  Helper::kvToJsOptions([
                'yes'           =>  __( 'Yes' ),
                'no'            =>  __( 'No' )
            ]),
            'description'       =>  __( 'Will use gross prices for each products.' ),
        ], [
            'name'              =>  'ns_pos_order_types',
            'value'             =>  $options->get( 'ns_pos_order_types' ),
            'label'             =>  __( 'Order Types' ), 
            'type'              =>  'multiselect',
            'options'           =>  Helper::kvToJsOptions([
                'delivery'      =>  __( 'Delivery' ),
                'take_away'     =>  __( 'Take Away' )
            ]),
            'description'       =>  __( 'Control the order type enabled.' ),
        ],
    ]
];