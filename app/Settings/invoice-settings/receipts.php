<?php

use App\Classes\Hook;
use App\Services\Helper;

$tags = [
    __( 'Available tags : ' ) . '<br>' .
    __( '{store_name}: displays the store name.' ),
    __( '{store_email}: displays the store email.' ),
    __( '{store_phone}: displays the store phone number.' ),
    __( '{cashier_name}: displays the cashier name.' ),
    __( '{cashier_id}: displays the cashier id.' ),
    __( '{order_code}: displays the order code.' ),
    __( '{order_date}: displays the order date.' ),
    __( '{order_type}: displays the order type.' ),
    __( '{customer_first_name}: displays the customer first name.' ),
    __( '{customer_last_name}: displays the customer last name.' ),
    __( '{customer_email}: displays the customer email.' ),
    __( '{shipping_first_name}: displays the shipping first name.' ),
    __( '{shipping_last_name}: displays the shipping last name.' ),
    __( '{shipping_phone}: displays the shipping phone.' ),
    __( '{shipping_address_1}: displays the shipping address_1.' ),
    __( '{shipping_address_2}: displays the shipping address_2.' ),
    __( '{shipping_country}: displays the shipping country.' ),
    __( '{shipping_city}: displays the shipping city.' ),
    __( '{shipping_pobox}: displays the shipping pobox.' ),
    __( '{shipping_company}: displays the shipping company.' ),
    __( '{shipping_email}: displays the shipping email.' ),
    __( '{billing_first_name}: displays the billing first name.' ),
    __( '{billing_last_name}: displays the billing last name.' ),
    __( '{billing_phone}: displays the billing phone.' ),
    __( '{billing_address_1}: displays the billing address_1.' ),
    __( '{billing_address_2}: displays the billing address_2.' ),
    __( '{billing_country}: displays the billing country.' ),
    __( '{billing_city}: displays the billing city.' ),
    __( '{billing_pobox}: displays the billing pobox.' ),
    __( '{billing_company}: displays the billing company.' ),
    __( '{billing_email}: displays the billing email.' ),
];

return [
    'label' => __( 'Receipts' ),
    'fields' => [
        [
            'label' => __( 'Receipt Template' ),
            'type' => 'select',
            'options' => Helper::kvToJsOptions( [
                'default' => __( 'Default' ),
            ] ),
            'name' => 'ns_invoice_receipt_template',
            'value' => ns()->option->get( 'ns_invoice_receipt_template' ),
            'description' => __( 'Choose the template that applies to receipts' ),
        ], [
            'label' => __( 'Receipt Logo' ),
            'type' => 'media',
            'name' => 'ns_invoice_receipt_logo',
            'value' => ns()->option->get( 'ns_invoice_receipt_logo' ),
            'description' => __( 'Provide a URL to the logo.' ),
        ], [
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
            'label' => __( 'Receipt Footer' ),
            'type' => 'textarea',
            'name' => 'ns_invoice_receipt_footer',
            'value' => ns()->option->get( 'ns_invoice_receipt_footer' ),
            'description' => __( 'If you would like to add some disclosure at the bottom of the receipt.' ),
        ], [
            'label' => __( 'Column A' ),
            'type' => 'textarea',
            'name' => 'ns_invoice_receipt_column_a',
            'value' => ns()->option->get( 'ns_invoice_receipt_column_a' ),
            'description' => implode( '<br/>', Hook::filter( 'ns-receipts-settings-tags', $tags ) ),
        ], [
            'label' => __( 'Column B' ),
            'type' => 'textarea',
            'name' => 'ns_invoice_receipt_column_b',
            'value' => ns()->option->get( 'ns_invoice_receipt_column_b' ),
            'description' => implode( '<br/>', Hook::filter( 'ns-receipts-settings-tags', $tags ) ),
        ],
    ],
];
