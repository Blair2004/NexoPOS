<?php

use App\Classes\FormInput;
use App\Classes\SettingForm;
use App\Services\Helper;

return SettingForm::tab(
    identifier: 'general',
    label: __( 'General Shipping' ),
    fields: SettingForm::fields(
        FormInput::select(
            name: 'shipping_type',
            label: __( 'Shipping Type' ),
            options: Helper::kvToJsOptions( [
                'flat' => __( 'Flat' ),
            ] ),
            value: 'flat',
            description: __( 'Define how the shipping is calculated.' ),
        ),
        FormInput::text(
            name: 'shipping',
            label: __( 'Shipping Fees' ),
            description: __( 'Define shipping fees.' ),
            value: 0,
            validation: 'number',
        )
    )
);
