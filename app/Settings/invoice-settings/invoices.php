<?php

use App\Services\Helper;

$tags = include dirname( __FILE__ ) . '/tags.php';

/**
 * Options that apply to the invoice document only. The receipt
 * counterpart uses the "ns_invoice_receipt_*" options (Receipts tab).
 */
return [
    'label' => __( 'Invoices' ),
    'fields' => [
        [
            'label' => __( 'Invoice Template' ),
            'type' => 'select',
            'options' => Helper::kvToJsOptions( [
                'default' => __( 'Default' ),
            ] ),
            'name' => 'ns_invoice_template',
            'value' => ns()->option->get( 'ns_invoice_template', 'default' ),
            'description' => __( 'Choose the template that applies to invoices' ),
        ], [
            'label' => __( 'Invoice Font Scale' ),
            'type' => 'select',
            'options' => Helper::kvToJsOptions( [
                70 => '70%',
                80 => '80%',
                90 => '90%',
                100 => '100%',
                110 => '110%',
                120 => '120%',
                130 => '130%',
                150 => '150%',
                200 => '200%',
            ] ),
            'name' => 'ns_invoice_font_scale',
            'value' => ns()->option->get( 'ns_invoice_font_scale', 100 ),
            'description' => __( 'Scale all invoice fonts by the selected percentage (100% is the default size).' ),
        ], [
            'label' => __( 'Invoice Logo' ),
            'type' => 'media',
            'name' => 'ns_invoice_logo',
            'value' => ns()->option->get( 'ns_invoice_logo' ),
            'description' => __( 'Provide a URL to the logo. Falls back to the store logo when empty.' ),
        ], [
            'label' => __( 'Show Store Details' ),
            'type' => 'switch',
            'options' => Helper::kvToJsOptions( [
                'yes' => __( 'Yes' ),
                'no' => __( 'No' ),
            ] ),
            'name' => 'ns_invoice_show_store_details',
            'value' => ns()->option->get( 'ns_invoice_show_store_details', 'yes' ),
            'description' => __( 'Will display the store and order details block on invoices.' ),
        ], [
            'label' => __( 'Show Billing Details' ),
            'type' => 'switch',
            'options' => Helper::kvToJsOptions( [
                'yes' => __( 'Yes' ),
                'no' => __( 'No' ),
            ] ),
            'name' => 'ns_invoice_show_billing_details',
            'value' => ns()->option->get( 'ns_invoice_show_billing_details', 'yes' ),
            'description' => __( 'Will display the billing address block on invoices.' ),
        ], [
            'label' => __( 'Show Shipping Details' ),
            'type' => 'switch',
            'options' => Helper::kvToJsOptions( [
                'yes' => __( 'Yes' ),
                'no' => __( 'No' ),
            ] ),
            'name' => 'ns_invoice_show_shipping_details',
            'value' => ns()->option->get( 'ns_invoice_show_shipping_details', 'yes' ),
            'description' => __( 'Will display the shipping address block on invoices.' ),
        ], [
            'label' => __( 'Hide N/A Fields' ),
            'type' => 'switch',
            'options' => Helper::kvToJsOptions( [
                'yes' => __( 'Yes' ),
                'no' => __( 'No' ),
            ] ),
            'name' => 'ns_invoice_hide_na_values',
            'value' => ns()->option->get( 'ns_invoice_hide_na_values', 'no' ),
            'description' => __( 'Will hide invoice fields that have no value (N/A), including blocks where every field is empty.' ),
        ], [
            'label' => __( 'Show Unit Price' ),
            'type' => 'switch',
            'options' => Helper::kvToJsOptions( [
                'yes' => __( 'Yes' ),
                'no' => __( 'No' ),
            ] ),
            'name' => 'ns_invoice_show_unit_price',
            'value' => ns()->option->get( 'ns_invoice_show_unit_price', 'yes' ),
            'description' => __( 'Will display the unit price column on invoices.' ),
        ], [
            'label' => __( 'Show Discount' ),
            'type' => 'switch',
            'options' => Helper::kvToJsOptions( [
                'yes' => __( 'Yes' ),
                'no' => __( 'No' ),
            ] ),
            'name' => 'ns_invoice_show_discount',
            'value' => ns()->option->get( 'ns_invoice_show_discount', 'yes' ),
            'description' => __( 'Will display the discount column on invoices.' ),
        ], [
            'label' => __( 'Show Tax Column' ),
            'type' => 'switch',
            'options' => Helper::kvToJsOptions( [
                'yes' => __( 'Yes' ),
                'no' => __( 'No' ),
            ] ),
            'name' => 'ns_invoice_show_tax_column',
            'value' => ns()->option->get( 'ns_invoice_show_tax_column', 'yes' ),
            'description' => __( 'Will display the tax column on invoices.' ),
        ], [
            'label' => __( 'Invoice Footer' ),
            'type' => 'textarea',
            'name' => 'ns_invoice_footer',
            'value' => ns()->option->get( 'ns_invoice_footer' ),
            'description' => __( 'If you would like to add some disclosure at the bottom of the invoice.' ),
        ], [
            'label' => __( 'Column A' ),
            'type' => 'textarea',
            'name' => 'ns_invoice_column_a',
            'value' => ns()->option->get( 'ns_invoice_column_a' ),
            'description' => implode( '<br/>', $tags ),
        ], [
            'label' => __( 'Column B' ),
            'type' => 'textarea',
            'name' => 'ns_invoice_column_b',
            'value' => ns()->option->get( 'ns_invoice_column_b' ),
            'description' => implode( '<br/>', $tags ),
        ],
    ],
];
