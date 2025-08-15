<?php

use App\Classes\FormInput;
use App\Classes\SettingForm;
use App\Crud\CustomerCrud;
use App\Services\Helper;

return SettingForm::tab(
    identifier: 'billing',
    label: __( 'Billing Address' ),
    fields: SettingForm::fields(
        FormInput::switch(
            label: __( 'Use Customer Shipping' ),
            options: Helper::kvToJsOptions( [ __( 'No' ), __( 'Yes' )] ),
            name: '_use_customer_billing',
            description: __( 'Define whether the customer billing information should be used.' ),
        ),
        ...( new CustomerCrud )->getForm()[ 'tabs' ][ 'billing' ][ 'fields' ],
    )
);
