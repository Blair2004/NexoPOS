<?php

use App\Services\Helper;

$tags = include dirname( __FILE__ ) . '/tags.php';

/**
 * Receipt specific options. The options shared with the invoice
 * document live on the "Display" tab (display.php).
 */
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
            'label' => __( 'Receipt Font Scale' ),
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
            'name' => 'ns_invoice_receipt_font_scale',
            'value' => ns()->option->get( 'ns_invoice_receipt_font_scale', 100 ),
            'description' => __( 'Scale all receipt fonts by the selected percentage (100% is the default size).' ),
        ], [
            'label' => __( 'Receipt Logo' ),
            'type' => 'media',
            'name' => 'ns_invoice_receipt_logo',
            'value' => ns()->option->get( 'ns_invoice_receipt_logo' ),
            'description' => __( 'Provide a URL to the logo.' ),
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
