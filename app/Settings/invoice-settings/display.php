<?php

use App\Services\Helper;

/**
 * Options that apply to both documents (receipts and invoices).
 * They used to live on the "Receipts" tab, but their behavior was
 * always documented as "receipt/invoice". The invoice template now
 * honors them as well.
 */
return [
    'label' => __( 'Display' ),
    'fields' => [
        [
            'label' => __( 'Merge Products On Receipt/Invoice' ),
            'type' => 'switch',
            'options' => Helper::kvToJsOptions( [
                'no' => __( 'No' ),
                'yes' => __( 'Yes' ),
            ] ),
            'name' => 'ns_invoice_merge_similar_products',
            'value' => ns()->option->get( 'ns_invoice_merge_similar_products' ),
            'description' => __( 'All similar products will be merged to avoid a paper waste for the receipt/invoice.' ),
        ], [
            'label' => __( 'Show Tax Breakdown' ),
            'type' => 'switch',
            'options' => Helper::kvToJsOptions( [
                'no' => __( 'No' ),
                'yes' => __( 'Yes' ),
            ] ),
            'name' => 'ns_invoice_display_tax_breakdown',
            'value' => ns()->option->get( 'ns_invoice_display_tax_breakdown' ),
            'description' => __( 'Will display the tax breakdown on the receipt/invoice.' ),
        ], [
            'label' => __( 'Show Product Unit' ),
            'type' => 'switch',
            'options' => Helper::kvToJsOptions( [
                'yes' => __( 'Yes' ),
                'no' => __( 'No' ),
            ] ),
            'name' => 'ns_invoice_show_product_unit',
            'value' => ns()->option->get( 'ns_invoice_show_product_unit', 'yes' ),
            'description' => __( 'Will display the unit name next to each product on receipts and invoices.' ),
        ], [
            'label' => __( 'Show Sub Total' ),
            'type' => 'switch',
            'options' => Helper::kvToJsOptions( [
                'yes' => __( 'Yes' ),
                'no' => __( 'No' ),
            ] ),
            'name' => 'ns_invoice_show_subtotal',
            'value' => ns()->option->get( 'ns_invoice_show_subtotal', 'yes' ),
            'description' => __( 'Will display the subtotal row on receipts and invoices.' ),
        ], [
            'label' => __( 'Show Payment Rows' ),
            'type' => 'switch',
            'options' => Helper::kvToJsOptions( [
                'yes' => __( 'Yes' ),
                'no' => __( 'No' ),
            ] ),
            'name' => 'ns_invoice_show_payment_rows',
            'value' => ns()->option->get( 'ns_invoice_show_payment_rows', 'yes' ),
            'description' => __( 'Will display per-payment type rows (Cash, Bank, etc.) on receipts and invoices.' ),
        ], [
            'label' => __( 'Show Change / Due' ),
            'type' => 'switch',
            'options' => Helper::kvToJsOptions( [
                'yes' => __( 'Yes' ),
                'no' => __( 'No' ),
            ] ),
            'name' => 'ns_invoice_show_change_due',
            'value' => ns()->option->get( 'ns_invoice_show_change_due', 'yes' ),
            'description' => __( 'Will display the Change or Due row on receipts and the Balance Due row on invoices.' ),
        ],
    ],
];
