<?php

use App\Classes\FormInput;
use App\Classes\SettingForm;

return SettingForm::tab(
    identifier: 'scale-barcode',
    label: __('Scale Barcode'),
    fields: [
        FormInput::text(
            label: __('Barcode Prefix'),
            name: 'ns_scale_barcode_prefix',
            value: ns()->option->get('ns_scale_barcode_prefix', '2'),
            validation: 'required',
            description: __('The prefix that identifies scale barcodes (typically "2" or "21-29" for EAN-13). This is the first digit(s) of the barcode.')
        ),

        FormInput::select(
            label: __('Barcode Type'),
            name: 'ns_scale_barcode_type',
            options: [
                ['label' => __('Weight'), 'value' => 'weight'],
                ['label' => __('Price'), 'value' => 'price'],
            ],
            value: ns()->option->get('ns_scale_barcode_type', 'weight'),
            validation: 'required',
            description: __('Select whether the scale barcode encodes weight (in grams) or price (in cents).')
        ),

        FormInput::number(
            label: __('Product Code Length'),
            name: 'ns_scale_barcode_product_length',
            value: ns()->option->get('ns_scale_barcode_product_length', 5),
            validation: 'required|integer|min:1|max:10',
            description: __('The number of digits used for the product code in the scale barcode (typically 5).')
        ),

        FormInput::number(
            label: __('Weight/Price Value Length'),
            name: 'ns_scale_barcode_value_length',
            value: ns()->option->get('ns_scale_barcode_value_length', 5),
            validation: 'required|integer|min:1|max:10',
            description: __('The number of digits used for weight (in grams) or price (in cents) in the scale barcode (typically 5).')
        ),

        FormInput::custom(
            label: __( 'Settings Preview' ),
            component: 'nsScaleSettingsPreview',
        )
    ],
);
