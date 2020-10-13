<?php

use App\Services\Helper;

return [
    'label' =>  __( 'Printing' ),
    'fields'    =>  [
        [
            'name'              =>  'ns_pos_printing_document',
            'value'             =>  $options->get( 'ns_pos_printing_document' ),
            'label'             =>  __( 'Printed Document' ), 
            'type'              =>  'select',
            'options'           =>  Helper::kvToJsOptions([
                'invoice'       =>  __( 'Invoice' ),
                'receipt'       =>  __( 'Receipt' )
            ]),
            'description'       =>  __( 'Choose the document used for printing aster a sale.' ),
        ], [
            'name'              =>  'ns_pos_printing_enabled_for',
            'value'             =>  $options->get( 'ns_pos_printing_enabled_for' ),
            'label'             =>  __( 'Printing Enabled For' ), 
            'type'              =>  'select',
            'options'           =>  Helper::kvToJsOptions([
                'disabled'              =>  __( 'Disabled' ),
                'all_orders'            =>  __( 'All Orders' ),
                'partially_paid_orders' =>  __( 'From Partially Paid Orders' ),
                'only_paid_ordes'       =>  __( 'Only Paid Orders' ),
            ]),
            'description'       =>  __( 'Determine when the printing should be enabled.' ),
        ], 
    ]
];