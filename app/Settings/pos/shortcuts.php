<?php

use App\Services\Helper;

return [
    'label' =>  __( 'Keyboard Shortcuts' ),
    'fields'    =>  [
        [
            'name'              =>  'ns_pos_keyboard_cancel_order',
            'value'             =>  $options->get( 'ns_pos_keyboard_cancel_order' ),
            'label'             =>  __( 'Cancel Order' ), 
            'type'              =>  'text',
            'description'       =>  __( 'Keyboard shortcut to cancel the current order.' ),
        ], [
            'name'              =>  'ns_pos_keyboard_hold_order',
            'value'             =>  $options->get( 'ns_pos_keyboard_hold_order' ),
            'label'             =>  __( 'Hold Order' ), 
            'type'              =>  'text',
            'description'       =>  __( 'Keyboard shortcut to hold the current order.' ),
        ], [
            'name'              =>  'ns_pos_keyboard_create_customer',
            'value'             =>  $options->get( 'ns_pos_keyboard_create_customer' ),
            'label'             =>  __( 'Create Customer' ), 
            'type'              =>  'text',
            'description'       =>  __( 'Keyboard shortcut to create a customer.' ),
        ], [
            'name'              =>  'ns_pos_keyboard_payment',
            'value'             =>  $options->get( 'ns_pos_keyboard_payment' ),
            'label'             =>  __( 'Proceed Payment' ), 
            'type'              =>  'text',
            'description'       =>  __( 'Keyboard shortcut to proceed to the payment.' ),
        ], [
            'name'              =>  'ns_pos_keyboard_shipping',
            'value'             =>  $options->get( 'ns_pos_keyboard_shipping' ),
            'label'             =>  __( 'Open Shipping' ), 
            'type'              =>  'text',
            'description'       =>  __( 'Keyboard shortcut to define shipping details.' ),
        ], [
            'name'              =>  'ns_pos_keyboard_note',
            'value'             =>  $options->get( 'ns_pos_keyboard_note' ),
            'label'             =>  __( 'Open Note' ), 
            'type'              =>  'text',
            'description'       =>  __( 'Keyboard shortcut to open the notes.' ),
        ], [
            'name'              =>  'ns_pos_keyboard_calculator',
            'value'             =>  $options->get( 'ns_pos_keyboard_calculator' ),
            'label'             =>  __( 'Open Calculator' ), 
            'type'              =>  'text',
            'description'       =>  __( 'Keyboard shortcut to open the calculator.' ),
        ], [
            'name'              =>  'ns_pos_keyboard_category_explorer',
            'value'             =>  $options->get( 'ns_pos_keyboard_category_explorer' ),
            'label'             =>  __( 'Open Category Explorer' ), 
            'type'              =>  'text',
            'description'       =>  __( 'Keyboard shortcut to open the category explorer.' ),
        ], [
            'name'              =>  'ns_pos_keyboard_order_type',
            'value'             =>  $options->get( 'ns_pos_keyboard_order_type' ),
            'label'             =>  __( 'Order Type Selector' ), 
            'type'              =>  'text',
            'description'       =>  __( 'Keyboard shortcut to open the order type selector.' ),
        ], [
            'name'              =>  'ns_pos_keyboard_fullscreen',
            'value'             =>  $options->get( 'ns_pos_keyboard_fullscreen' ),
            'label'             =>  __( 'Toggle Fullscreen' ), 
            'type'              =>  'text',
            'description'       =>  __( 'Keyboard shortcut to toggle fullscreen.' ),
        ], [
            'name'              =>  'ns_pos_keyboard_quick_search',
            'value'             =>  $options->get( 'ns_pos_keyboard_quick_search' ),
            'label'             =>  __( 'Quick Search' ), 
            'type'              =>  'text',
            'description'       =>  __( 'Keyboard shortcut open the quick search popup.' ),
        ], [
            'name'              =>  'ns_pos_amount_shortcut',
            'value'             =>  $options->get( 'ns_pos_amount_shortcut' ),
            'label'             =>  __( 'Amount Shortcuts' ), 
            'type'              =>  'text',
            'description'       =>  __( 'The amount numbers shortcuts separated with a "|".' ),
        ], 
    ]
];