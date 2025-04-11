<?php

use App\Crud\CustomerCrud;
use App\Services\Helper;
use App\Classes\FormInput;
use App\Classes\SettingForm;

return SettingForm::tab(
    identifier: 'shipping',
    label: __( 'Shipping Address' ),
    fields: SettingForm::fields(
        FormInput::switch(
            label: __( 'Use Customer Shipping' ),
            options: Helper::kvToJsOptions([ __( 'No' ), __( 'Yes' )]),
            name: '_use_customer_shipping',
            description: __( 'Define whether the customer shipping information should be used.' ),
        ),
        ...( new CustomerCrud )->getForm()[ 'tabs' ][ 'shipping' ][ 'fields' ],
    )
);