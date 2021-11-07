<?php

use App\Classes\Hook;
use App\Services\Helper;

return [
    'label'     =>      __( 'Receipts' ),
    'fields'    =>      [
        [
            'label'     =>  __( 'Receipt Template' ),
            'type'      =>  'select',
            'options'   =>  Helper::kvToJsOptions([
                'default'   =>  __( 'Default' )
            ]),
            'name'      =>  'ns_invoice_receipt_template',
            'value'     =>  $options->get( 'ns_invoice_receipt_template' ),
            'description'   =>  __( 'Choose the template that applies to receipts' )
        ], [
            'label'     =>  __( 'Receipt Logo' ),
            'type'      =>  'media',
            'name'      =>  'ns_invoice_receipt_logo',
            'value'     =>  $options->get( 'ns_invoice_receipt_logo' ),
            'description'   =>  __( 'Provide a URL to the logo.' )
        ], [
            'label'     =>  __( 'Merge Products On Receipt/Invoice' ),
            'type'      =>  'switch',
            'options'   =>  Helper::kvToJsOptions([ 
                'no'    =>  __( 'No' ), 
                'yes'   =>  __( 'Yes' ) 
            ]),
            'name'      =>  'ns_invoice_merge_similar_products',
            'value'     =>  $options->get( 'ns_invoice_merge_similar_products' ),
            'description'   =>  __( 'All similar products will be merged to avoid a paper waste for the receipt/invoice.' )
        ], [
            'label'     =>  __( 'Receipt Footer' ),
            'type'      =>  'textarea',
            'name'      =>  'ns_invoice_receipt_footer',
            'value'     =>  $options->get( 'ns_invoice_receipt_footer' ),
            'description'   =>  __( 'If you would like to add some disclosure at the bottom of the receipt.' )
        ], [
            'label'         =>  __( 'Column A' ),
            'type'          =>  'textarea',
            'name'          =>  'ns_invoice_receipt_column_a',
            'value'         =>  $options->get( 'ns_invoice_receipt_column_a' ),
            'description'   =>  
            Hook::filter( 'ns-receipts-settings-tags', [
                __( 'Available tags : ' ) . '<br>' .
                __( '{store_name}: displays the store name.' ) . "<br>",
                __( '{store_email}: displays the store email.' ), "<br>",
                __( '{store_phone}: displays the store phone number.' ), "<br>",
                __( '{cashier_name}: displays the cashier name.' ), "<br>",
                __( '{cashier_id}: displays the cashier id.' ), "<br>",
                __( '{order_code}: displays the order code.' ), "<br>",
                __( '{order_date}: displays the order date.' ), "<br>",
                __( '{customer_name}: displays the customer name.' ), "<br>",
                __( '{customer_email}: displays the customer email.' ), "<br>",
                __( '{shipping_name}: displays the shipping name.' ), "<br>",
                __( '{shipping_surname}: displays the shipping surname.' ), "<br>",
                __( '{shipping_phone}: displays the shipping phone.' ), "<br>",
                __( '{shipping_address_1}: displays the shipping address_1.' ), "<br>",
                __( '{shipping_address_2}: displays the shipping address_2.' ), "<br>",
                __( '{shipping_country}: displays the shipping country.' ), "<br>",
                __( '{shipping_city}: displays the shipping city.' ), "<br>",
                __( '{shipping_pobox}: displays the shipping pobox.' ), "<br>",
                __( '{shipping_company}: displays the shipping company.' ), "<br>",
                __( '{shipping_email}: displays the shipping email.' ), "<br>",
                __( '{billing_name}: displays the billing name.' ), "<br>",
                __( '{billing_surname}: displays the billing surname.' ), "<br>",
                __( '{billing_phone}: displays the billing phone.' ), "<br>",
                __( '{billing_address_1}: displays the billing address_1.' ), "<br>",
                __( '{billing_address_2}: displays the billing address_2.' ), "<br>",
                __( '{billing_country}: displays the billing country.' ), "<br>",
                __( '{billing_city}: displays the billing city.' ), "<br>",
                __( '{billing_pobox}: displays the billing pobox.' ), "<br>",
                __( '{billing_company}: displays the billing company.' ), "<br>",
                __( '{billing_email}: displays the billing email.' ) . "<br>"
            ])
        ], [
            'label'         =>  __( 'Column B' ),
            'type'          =>  'textarea',
            'name'          =>  'ns_invoice_receipt_column_b',
            'value'         =>  $options->get( 'ns_invoice_receipt_column_b' ),
            'description'   =>  
            Hook::filter( 'ns-receipts-settings-tags', [
                __( 'Available tags :' ) . '<br>',
                __( '{store_name}: displays the store name.' ) . "<br>",
                __( '{store_email}: displays the store email.' ) . "<br>",
                __( '{store_phone}: displays the store phone number.' ) . "<br>",
                __( '{cashier_name}: displays the cashier name.' ) . "<br>",
                __( '{cashier_id}: displays the cashier id.' ) . "<br>",
                __( '{order_code}: displays the order code.' ) . "<br>",
                __( '{order_date}: displays the order date.' ) . "<br>",
                __( '{customer_name}: displays the customer name.' ) . "<br>",
                __( '{customer_email}: displays the customer email.' ) . "<br>",
                __( '{shipping_name}: displays the shipping name.' ) . "<br>",
                __( '{shipping_surname}: displays the shipping surname.' ) . "<br>",
                __( '{shipping_phone}: displays the shipping phone.' ) . "<br>",
                __( '{shipping_address_1}: displays the shipping address_1.' ) . "<br>",
                __( '{shipping_address_2}: displays the shipping address_2.' ) . "<br>",
                __( '{shipping_country}: displays the shipping country.' ) . "<br>",
                __( '{shipping_city}: displays the shipping city.' ) . "<br>",
                __( '{shipping_pobox}: displays the shipping pobox.' ) . "<br>",
                __( '{shipping_company}: displays the shipping company.' ) . "<br>",
                __( '{shipping_email}: displays the shipping email.' ) . "<br>",
                __( '{billing_name}: displays the billing name.' ) . "<br>",
                __( '{billing_surname}: displays the billing surname.' ) . "<br>",
                __( '{billing_phone}: displays the billing phone.' ) . "<br>",
                __( '{billing_address_1}: displays the billing address_1.' ) . "<br>",
                __( '{billing_address_2}: displays the billing address_2.' ) . "<br>",
                __( '{billing_country}: displays the billing country.' ) . "<br>",
                __( '{billing_city}: displays the billing city.' ) . "<br>",
                __( '{billing_pobox}: displays the billing pobox.' ) . "<br>",
                __( '{billing_company}: displays the billing company.' ) . "<br>",
                __( '{billing_email}: displays the billing email.' ) . "<br>"
            ])
        ]
    ]
];