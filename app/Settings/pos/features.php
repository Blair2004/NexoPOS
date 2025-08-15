<?php

use App\Classes\FormInput;
use App\Models\Unit;
use App\Services\Helper;
use App\Services\OrdersService;

return [
    'label' => __( 'Features' ),
    'fields' => [
        FormInput::switch(
            label: __( 'Show Quantity' ),
            name: 'ns_pos_show_quantity',
            options: [
                ['label' => __( 'Yes' ), 'value' => 'yes'],
                ['label' => __( 'No' ), 'value' => 'no'],
            ],
            value: ns()->option->get( 'ns_pos_show_quantity' ),
            description: __( 'Will show the quantity selector while choosing a product. Otherwise the default quantity is set to 1.' )
        ),

        FormInput::switch(
            label: __( 'Merge Similar Items' ),
            name: 'ns_pos_items_merge',
            options: [
                ['label' => __( 'Yes' ), 'value' => 'yes'],
                ['label' => __( 'No' ), 'value' => 'no'],
            ],
            value: ns()->option->get( 'ns_pos_items_merge' ),
            description: __( 'Will enforce similar products to be merged from the POS.' )
        ),

        FormInput::switch(
            label: __( 'Allow Wholesale Price' ),
            name: 'ns_pos_allow_wholesale_price',
            options: [
                ['label' => __( 'Yes' ), 'value' => 'yes'],
                ['label' => __( 'No' ), 'value' => 'no'],
            ],
            value: ns()->option->get( 'ns_pos_allow_wholesale_price' ),
            description: __( 'Define if the wholesale price can be selected on the POS.' )
        ),

        FormInput::switch(
            label: __( 'Allow Decimal Quantities' ),
            name: 'ns_pos_allow_decimal_quantities',
            options: [
                ['label' => __( 'Yes' ), 'value' => 'yes'],
                ['label' => __( 'No' ), 'value' => 'no'],
            ],
            value: ns()->option->get( 'ns_pos_allow_decimal_quantities' ),
            description: __( 'Will change the numeric keyboard for allowing decimal for quantities. Only for "default" numpad.' )
        ),

        FormInput::switch(
            label: __( 'Quick Product' ),
            name: 'ns_pos_quick_product',
            options: [
                ['label' => __( 'Yes' ), 'value' => 'yes'],
                ['label' => __( 'No' ), 'value' => 'no'],
            ],
            value: ns()->option->get( 'ns_pos_quick_product' ),
            description: __( 'Allow quick product to be created from the POS.' )
        ),

        FormInput::select(
            label: __( 'Quick Product Default Unit' ),
            name: 'ns_pos_quick_product_default_unit',
            options: Helper::toJsOptions( Unit::get(), [ 'id', 'name' ] ),
            value: ns()->option->get( 'ns_pos_quick_product_default_unit' ),
            description: __( 'Set what unit is assigned by default to all quick product.' )
        ),

        FormInput::switch(
            label: __( 'Editable Unit Price' ),
            name: 'ns_pos_unit_price_ediable',
            options: [
                ['label' => __( 'Yes' ), 'value' => 'yes'],
                ['label' => __( 'No' ), 'value' => 'no'],
            ],
            value: ns()->option->get( 'ns_pos_unit_price_ediable' ),
            description: __( 'Allow product unit price to be edited.' )
        ),

        FormInput::switch(
            label: __( 'Show Price With Tax' ),
            name: 'ns_pos_price_with_tax',
            options: [
                ['label' => __( 'Yes' ), 'value' => 'yes'],
                ['label' => __( 'No' ), 'value' => 'no'],
            ],
            value: ns()->option->get( 'ns_pos_price_with_tax' ),
            description: __( 'Will display price with tax for each products.' )
        ),

        FormInput::multiselect(
            label: __( 'Order Types' ),
            name: 'ns_pos_order_types',
            options: Helper::kvToJsOptions( app()->make( OrdersService::class )->getTypeLabels() ),
            value: ns()->option->get( 'ns_pos_order_types' ),
            description: __( 'Control the order type enabled.' )
        ),

        FormInput::switch(
            label: __( 'Numpad' ),
            name: 'ns_pos_numpad',
            options: [
                ['label' => __( 'Default' ), 'value' => 'default'],
                ['label' => __( 'Advanced' ), 'value' => 'advanced'],
            ],
            value: ns()->option->get( 'ns_pos_numpad' ),
            description: __( 'Will set what is the numpad used on the POS screen.' )
        ),

        FormInput::switch(
            label: __( 'Force Barcode Auto Focus' ),
            name: 'ns_pos_force_autofocus',
            options: [
                ['label' => __( 'Yes' ), 'value' => 'yes'],
                ['label' => __( 'No' ), 'value' => 'no'],
            ],
            value: ns()->option->get( 'ns_pos_force_autofocus' ),
            description: __( 'Will permanently enable barcode autofocus to ease using a barcode reader.' )
        ),

        FormInput::switch(
            label: __( 'Hide Exhausted Products' ),
            name: 'ns_pos_hide_exhausted_products',
            options: [
                ['label' => __( 'Yes' ), 'value' => 'yes'],
                ['label' => __( 'No' ), 'value' => 'no'],
            ],
            value: ns()->option->get( 'ns_pos_hide_exhausted_products' ),
            description: __( 'Will hide exhausted products from selection on the POS.' )
        ),

        FormInput::switch(
            label: __( 'Hide Empty Category' ),
            name: 'ns_pos_hide_empty_categories',
            options: [
                ['label' => __( 'Yes' ), 'value' => 'yes'],
                ['label' => __( 'No' ), 'value' => 'no'],
            ],
            value: ns()->option->get( 'ns_pos_hide_empty_categories' ),
            description: __( 'Category with no or exhausted products will be hidden from selection.' )
        ),

        FormInput::switch(
            label: __( 'Enable Action Permission' ),
            description: __( 'Will allow restrict certains feature behind a permission request.' ),
            name: 'ns_pos_action_permission_enabled',
            options: [
                ['label' => __( 'Yes' ), 'value' => 'yes'],
                ['label' => __( 'No' ), 'value' => 'no'],
            ],
            value: ns()->option->get( 'ns_pos_action_permission_enabled' ),
        ),
    ],
];
