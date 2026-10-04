<?php

use App\Classes\Hook;

$tags = [
    __( 'Available tags : ' ) . '<br>' .
    __( '{store_name}: displays the store name.' ),
    __( '{store_email}: displays the store email.' ),
    __( '{store_phone}: displays the store phone number.' ),
    __( '{store_address}: displays the store address.' ),
    __( '{store_city}: displays the store city.' ),
    __( '{store_pobox}: displays the store pobox.' ),
    __( '{cashier_name}: displays the cashier name.' ),
    __( '{cashier_id}: displays the cashier id.' ),
    __( '{order_code}: displays the order code.' ),
    __( '{order_date}: displays the order date.' ),
    __( '{order_date_only}: displays the order date without time.' ),
    __( '{order_time}: displays the order time.' ),
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

/**
 * Shared tag documentation used by both the receipt
 * and the invoice "Column A"/"Column B" settings fields.
 */
return Hook::filter( 'ns-receipts-settings-tags', $tags );
