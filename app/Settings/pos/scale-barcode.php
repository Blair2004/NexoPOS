<?php

use App\Classes\FormInput;
use App\Classes\SettingForm;

return SettingForm::tab(
    identifier: 'scale-barcode',
    label: __('Scale Barcode'),
    footer: SettingForm::tabFooter(
        extraComponents: [ 'nsScaleSettingsPreview' ]
    ),
    fields: [
        FormInput::switch(
            label: __('Enable Scale Barcode'),
            name: 'ns_scale_barcode_enabled',
            options: [
                ['label' => __('Yes'), 'value' => 'yes'],
                ['label' => __('No'), 'value' => 'no'],
            ],
            value: ns()->option->get('ns_scale_barcode_enabled', 'no'),
            description: __('Enable support for scale barcodes that encode product code and weight/price information.')
        ),

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

        FormInput::textarea(
            label: __('Configuration Example'),
            name: 'ns_scale_barcode_example',
            value: getExampleText(),
            disabled: true,
            description: __('Example of how scale barcodes will be interpreted with current settings.')
        ),
    ],
);

/**
 * Get example text based on current configuration
 */
function getExampleText()
{
    $prefix = ns()->option->get('ns_scale_barcode_prefix', '2');
    $type = ns()->option->get('ns_scale_barcode_type', 'weight');
    $productLength = ns()->option->get('ns_scale_barcode_product_length', 5);
    $valueLength = ns()->option->get('ns_scale_barcode_value_length', 5);

    $example = "Format: {$prefix}" . str_repeat('X', $productLength) . str_repeat('W', $valueLength) . 'C' . "\n\n";
    $example .= "Where:\n";
    $example .= "- {$prefix} = Scale barcode prefix\n";
    $example .= "- " . str_repeat('X', $productLength) . " = Product code ({$productLength} digits)\n";
    
    if ($type === 'weight') {
        $example .= "- " . str_repeat('W', $valueLength) . " = Weight in grams ({$valueLength} digits)\n";
        $example .= "- C = Check digit\n\n";
        $example .= "Example: 2123450012349\n";
        $example .= "- Product code: 12345\n";
        $example .= "- Weight: 00123 grams = 0.123 kg";
    } else {
        $example .= "- " . str_repeat('W', $valueLength) . " = Price in cents ({$valueLength} digits)\n";
        $example .= "- C = Check digit\n\n";
        $example .= "Example: 2123450012349\n";
        $example .= "- Product code: 12345\n";
        $example .= "- Price: 00123 cents = $1.23";
    }

    return $example;
}
