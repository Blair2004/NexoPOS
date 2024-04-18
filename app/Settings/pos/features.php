<?php

use App\Models\Unit;
use App\Services\Helper;
use App\Services\OrdersService;

return [
    'label' => __( 'Features' ),
    'fields' => [
        [
            'name' => 'ns_pos_show_quantity',
            'value' => ns()->option->get( 'ns_pos_show_quantity' ),
            'label' => __( 'Show Quantity' ),
            'type' => 'switch',
            'options' => Helper::kvToJsOptions( [
                'yes' => __( 'Yes' ),
                'no' => __( 'No' ),
            ] ),
            'description' => __( 'Will show the quantity selector while choosing a product. Otherwise the default quantity is set to 1.' ),
        ], [
            'name' => 'ns_pos_items_merge',
            'value' => ns()->option->get( 'ns_pos_items_merge' ),
            'label' => __( 'Merge Similar Items' ),
            'type' => 'switch',
            'options' => Helper::kvToJsOptions( [
                'yes' => __( 'Yes' ),
                'no' => __( 'No' ),
            ] ),
            'description' => __( 'Will enforce similar products to be merged from the POS.' ),
        ], [
            'name' => 'ns_pos_allow_wholesale_price',
            'value' => ns()->option->get( 'ns_pos_allow_wholesale_price' ),
            'label' => __( 'Allow Wholesale Price' ),
            'type' => 'switch',
            'options' => Helper::kvToJsOptions( [
                'yes' => __( 'Yes' ),
                'no' => __( 'No' ),
            ] ),
            'description' => __( 'Define if the wholesale price can be selected on the POS.' ),
        ], [
            'name' => 'ns_pos_allow_decimal_quantities',
            'value' => ns()->option->get( 'ns_pos_allow_decimal_quantities' ),
            'label' => __( 'Allow Decimal Quantities' ),
            'type' => 'switch',
            'options' => Helper::kvToJsOptions( [
                'yes' => __( 'Yes' ),
                'no' => __( 'No' ),
            ] ),
            'description' => __( 'Will change the numeric keyboard for allowing decimal for quantities. Only for "default" numpad.' ),
        ], [
            'name' => 'ns_pos_quick_product',
            'value' => ns()->option->get( 'ns_pos_quick_product' ),
            'label' => __( 'Quick Product' ),
            'type' => 'switch',
            'options' => Helper::kvToJsOptions( [
                'yes' => __( 'Yes' ),
                'no' => __( 'No' ),
            ] ),
            'description' => __( 'Allow quick product to be created from the POS.' ),
        ], [
            'name' => 'ns_pos_quick_product_default_unit',
            'value' => ns()->option->get( 'ns_pos_quick_product_default_unit' ),
            'label' => __( 'Quick Product Default Unit' ),
            'type' => 'select',
            'options' => Helper::toJsOptions( Unit::get(), [ 'id', 'name' ] ),
            'description' => __( 'Set what unit is assigned by default to all quick product.' ),
        ], [
            'name' => 'ns_pos_unit_price_ediable',
            'value' => ns()->option->get( 'ns_pos_unit_price_ediable' ),
            'label' => __( 'Editable Unit Price' ),
            'type' => 'switch',
            'options' => Helper::kvToJsOptions( [
                'yes' => __( 'Yes' ),
                'no' => __( 'No' ),
            ] ),
            'description' => __( 'Allow product unit price to be edited.' ),
        ], [
            'name' => 'ns_pos_price_with_tax',
            'value' => ns()->option->get( 'ns_pos_price_with_tax' ),
            'label' => __( 'Show Price With Tax' ),
            'type' => 'switch',
            'options' => Helper::kvToJsOptions( [
                'yes' => __( 'Yes' ),
                'no' => __( 'No' ),
            ] ),
            'description' => __( 'Will display price with tax for each products.' ),
        ], [
            'name' => 'ns_pos_order_types',
            'value' => ns()->option->get( 'ns_pos_order_types' ),
            'label' => __( 'Order Types' ),
            'type' => 'multiselect',
            'options' => Helper::kvToJsOptions( app()->make( OrdersService::class )->getTypeLabels() ),
            'description' => __( 'Control the order type enabled.' ),
        ], [
            'name' => 'ns_pos_numpad',
            'value' => ns()->option->get( 'ns_pos_numpad' ),
            'label' => __( 'Numpad' ),
            'type' => 'switch',
            'options' => Helper::kvToJsOptions( [
                'default' => __( 'Default' ),
                'advanced' => __( 'Advanced' ),
            ] ),
            'description' => __( 'Will set what is the numpad used on the POS screen.' ),
        ], [
            'name' => 'ns_pos_force_autofocus',
            'value' => ns()->option->get( 'ns_pos_force_autofocus' ),
            'label' => __( 'Force Barcode Auto Focus' ),
            'type' => 'switch',
            'options' => Helper::kvToJsOptions( [
                'yes' => __( 'Yes' ),
                'no' => __( 'No' ),
            ] ),
            'description' => __( 'Will permanently enable barcode autofocus to ease using a barcode reader.' ),
        ],  [
            'name' => 'ns_pos_hide_exhausted_products',
            'value' => ns()->option->get( 'ns_pos_hide_exhausted_products' ),
            'label' => __( 'Hide Exhausted Products' ),
            'type' => 'switch',
            'options' => Helper::kvToJsOptions( [
                'yes' => __( 'Yes' ),
                'no' => __( 'No' ),
            ] ),
            'description' => __( 'Will hide exhausted products from selection on the POS.' ),
        ], [
            'name' => 'ns_pos_hide_empty_categories',
            'value' => ns()->option->get( 'ns_pos_hide_empty_categories' ),
            'label' => __( 'Hide Empty Category' ),
            'type' => 'switch',
            'options' => Helper::kvToJsOptions( [
                'yes' => __( 'Yes' ),
                'no' => __( 'No' ),
            ] ),
            'description' => __( 'Category with no or exhausted products will be hidden from selection.' ),
        ],
    ],
];
