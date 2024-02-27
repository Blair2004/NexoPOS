<?php

use App\Classes\Hook;
use App\Services\Helper;

return [
    'label' => __( 'Printing' ),
    'fields' => Hook::filter( 'ns-printing-settings-fields', [
        [
            'name' => 'ns_pos_printing_document',
            'value' => ns()->option->get( 'ns_pos_printing_document' ),
            'label' => __( 'Printed Document' ),
            'type' => 'select',
            'options' => Helper::kvToJsOptions( [
                'invoice' => __( 'Invoice' ),
                'receipt' => __( 'Receipt' ),
            ] ),
            'description' => __( 'Choose the document used for printing aster a sale.' ),
        ], [
            'name' => 'ns_pos_printing_enabled_for',
            'value' => ns()->option->get( 'ns_pos_printing_enabled_for' ),
            'label' => __( 'Printing Enabled For' ),
            'type' => 'select',
            'options' => Helper::kvToJsOptions( [
                'disabled' => __( 'Disabled' ),
                'all_orders' => __( 'All Orders' ),
                'partially_paid_orders' => __( 'From Partially Paid Orders' ),
                'only_paid_orders' => __( 'Only Paid Orders' ),
            ] ),
            'description' => __( 'Determine when the printing should be enabled.' ),
        ], [
            'name' => 'ns_pos_printing_gateway',
            'value' => ns()->option->get( 'ns_pos_printing_gateway' ),
            'label' => __( 'Printing Gateway' ),
            'type' => 'select',
            'options' => Helper::kvToJsOptions( [
                'default' => __( 'Default Printing (web)' ),
            ] ),
            'description' => __( 'Determine what is the gateway used for printing.' ),
        ],
    ] ),
];
