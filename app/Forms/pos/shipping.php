<?php

use App\Classes\FormInput;
use App\Classes\SettingForm;
use App\Crud\CustomerCrud;
use App\Services\Helper;

return SettingForm::tab(
    identifier: 'shipping',
    label: __( 'Shipping Address' ),
    fields: SettingForm::fields(
        FormInput::switch(
            label: __( 'Use Customer Shipping' ),
            options: Helper::kvToJsOptions( [ __( 'No' ), __( 'Yes' )] ),
            name: '_use_customer_shipping',
            description: __( 'Define whether the customer shipping information should be used.' ),
        ),
        ...( new CustomerCrud )->getForm()[ 'tabs' ][ 'shipping' ][ 'fields' ],
    )
);
