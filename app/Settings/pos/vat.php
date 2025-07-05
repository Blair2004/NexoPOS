<?php

use App\Classes\FormInput;
use App\Models\TaxGroup;
use App\Services\Helper;

$fields = [
    FormInput::select(
        label: __( 'VAT Type' ),
        name: 'ns_pos_vat',
        options: Helper::kvToJsOptions( [
            'disabled' => __( 'Disabled' ),
            'flat_vat' => __( 'Flat Rate' ),
            'variable_vat' => __( 'Flexible Rate' ),
            'products_vat' => __( 'Products Vat' ),
        ] ),
        value: ns()->option->get( 'ns_pos_vat' ),
        description: __( 'Determine the VAT type that should be used.' )
    ),
];

if ( in_array( ns()->option->get( 'ns_pos_vat' ), [ 'flat_vat' ] ) ) {
    $fields[] = FormInput::select(
        label: __( 'Tax Group' ),
        name: 'ns_pos_tax_group',
        options: Helper::toJsOptions( TaxGroup::get(), [ 'id', 'name' ] ),
        value: ns()->option->get( 'ns_pos_tax_group' ),
        description: __( 'Define the tax group that applies to the sales.' )
    );

    $fields[] = FormInput::select(
        label: __( 'Tax Type' ),
        name: 'ns_pos_tax_type',
        options: Helper::kvToJsOptions( [
            'inclusive' => __( 'Inclusive' ),
            'exclusive' => __( 'Exclusive' ),
        ] ),
        value: ns()->option->get( 'ns_pos_tax_type' ),
        description: __( 'Define how the tax is computed on sales.' )
    );
}

return [
    'label' => __( 'VAT Settings' ),
    'fields' => $fields,
];
