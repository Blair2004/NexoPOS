<?php
return [
    'label'     =>      __( 'Receipts' ),
    'fields'    =>      [
        [
            'label'     =>  __( 'Receipt Template' ),
            'type'      =>  'text',
            'name'      =>  'ns_invoice_receipt_template',
            'value'     =>  $options->get( 'ns_invoice_receipt_template' ),
            'description'   =>  __( 'Choose the template that applies to receipts' )
        ], [
            'label'     =>  __( 'Receipt Logo' ),
            'type'      =>  'text',
            'name'      =>  'ns_invoice_receipt_logo',
            'value'     =>  $options->get( 'ns_invoice_receipt_logo' ),
            'description'   =>  __( 'Provide a URL to the logo.' )
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
            'description'   =>  __( 'Available tags : ' ) . '<br>' .
            __( '{store_name}: displays the store name.' ) . "<br>" .
            __( '{store_email}: displays the store email.' ) . "<br>" .
            __( '{store_phone}: displays the store phone number.' ) . "<br>" .
            __( '{cashier_name}: displays the cashier name.' ) . "<br>" .
            __( '{cashier_id}: displays the cashier id.' ) . "<br>" .
            __( '{order_code}: displays the order code.' ) . "<br>" .
            __( '{order_date}: displays the order date.' ) . "<br>" .
            __( '{customer_name}: displays the customer name.' ) . "<br>" .
            __( '{customer_email}: displays the customer email.' ) . "<br>" .
            __( '{customer_address_1}: display the customer\'s address 1.' ) . "<br>" .
            __( '{customer_address_2}: display the customer\'s address 2.' ) . "<br>"
        ], [
            'label'         =>  __( 'Column B' ),
            'type'          =>  'textarea',
            'name'          =>  'ns_invoice_receipt_column_b',
            'value'         =>  $options->get( 'ns_invoice_receipt_column_b' ),
            'description'   =>  __( 'Available tags :' ) . '<br>' .
            __( '{store_name}: displays the store name.' ) . "<br>" .
            __( '{store_email}: displays the store email.' ) . "<br>" .
            __( '{store_phone}: displays the store phone number.' ) . "<br>" .
            __( '{cashier_name}: displays the cashier name.' ) . "<br>" .
            __( '{cashier_id}: displays the cashier id.' ) . "<br>" .
            __( '{order_code}: displays the order code.' ) . "<br>" .
            __( '{order_date}: displays the order date.' ) . "<br>" .
            __( '{customer_name}: displays the customer name.' ) . "<br>" .
            __( '{customer_email}: displays the customer email.' ) . "<br>" .
            __( '{customer_address_1}: display the customer\'s address 1.' ) . "<br>" .
            __( '{customer_address_2}: display the customer\'s address 2.' ) . "<br>"
        ]
    ]
];