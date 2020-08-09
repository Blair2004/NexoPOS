<?php

use App\Services\Helper;

return [
    'label'     =>  __( 'VAT settings' ),
    'fields'    =>  [
        [
            'label'         =>  __( 'Layout Type' ),
            'name'          =>  'ns_pos_vat',
            'type'          =>  'select',
            'description'   =>  __( 'Determine the VAT type that should be used.' ),
            'options'       =>  Helper::kvToJsOptions([
                'disabled'                  =>  __( 'Disabled' ),
                'flat_vat'                  =>  __( 'Flat Rate' ),
                'variable_vat'              =>  __( 'Flexible Rate' ),
                'products_vat'              =>  __( 'Products Vat' ),
                'products_flat_vat'         =>  __( 'Products & Flat Rate' ),
                'products_variables_vat'    =>  __( 'Products & Flexible Rate' ),
            ])
        ]
    ]
];