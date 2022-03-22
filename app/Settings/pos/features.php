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
            'name'              =>  'ns_pos_show_quantity',
            'value'             =>  $options->get( 'ns_pos_show_quantity' ),
            'label'             =>  __( 'Show Quantity' ), 
            'type'              =>  'switch',
            'options'           =>  Helper::kvToJsOptions([
                'yes'           =>  __( 'Yes' ),
                'no'            =>  __( 'No' )
            ]),
            'description'       =>  __( 'Will show the quantity selector while choosing a product. Otherwise the default quantity is set to 1.' ),
        ], [
            'name'              =>  'pos_items_merge',
            'value'             =>  $options->get( 'pos_items_merge' ),
            'label'             =>  __( 'Merge Similar Items' ), 
            'type'              =>  'switch',
            'options'           =>  Helper::kvToJsOptions([
                'yes'           =>  __( 'Yes' ),
                'no'            =>  __( 'No' )
            ]),
            'description'       =>  __( 'Will enforce similar products to be merged from the POS.' ),
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
            'options'           =>  Helper::kvToJsOptions( config( 'nexopos.orders.types-labels' ) ),
            'description'       =>  __( 'Control the order type enabled.' ),
        ],
    ]
];