<?php

use App\Models\TaxGroup;
use App\Services\Helper;

$fields     =   [
    [
        'label'         =>  __( 'VAT Type' ),
        'name'          =>  'ns_pos_vat',
        'type'          =>  'select',
        'value'         =>  $options->get( 'ns_pos_vat' ),
        'description'   =>  __( 'Determine the VAT type that should be used.' ),
        'options'       =>  Helper::kvToJsOptions([
            'disabled'                  =>  __( 'Disabled' ),
            'flat_vat'                  =>  __( 'Flat Rate' ),
            'variable_vat'              =>  __( 'Flexible Rate' ),
            'products_vat'              =>  __( 'Products Vat' ),
            'products_flat_vat'         =>  __( 'Products & Flat Rate' ),
            'products_variable_vat'     =>  __( 'Products & Flexible Rate' ),
        ])
    ]
];

if ( in_array( $options->get( 'ns_pos_vat' ), [ 'flat_vat', 'products_flat_vat' ] ) ) {
    $fields[]       =   [
        'type'      =>  'select',
        'name'      =>  'ns_pos_tax_group',
        'value'     =>  $options->get( 'ns_pos_tax_group' ),
        'options'   =>  Helper::toJsOptions( TaxGroup::get(), [ 'id', 'name' ] ),
        'label'     =>  __( 'Tax Group' ),
        'description'   =>  __( 'Define the tax group that applies to the sales.' )
    ];

    $fields[]       =   [
        'type'      =>  'select',
        'name'      =>  'ns_pos_tax_type',
        'value'     =>  $options->get( 'ns_pos_tax_type' ),
        'options'   =>  Helper::kvToJsOptions([
            'inclusive'     =>      __( 'Inclusive' ),
            'exclusive'     =>      __( 'Exclusive' )
        ]),
        'label'     =>  __( 'Tax Type' ),
        'description'   =>  __( 'Define how the tax is computed on sales.' )
    ];
}

return [
    'label'     =>  __( 'VAT Settings' ),
    'fields'    =>  $fields
];