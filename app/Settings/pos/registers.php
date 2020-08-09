<?php

use App\Services\Helper;

$cashRegisters  =   [
    [
        'name'          =>  'ns_pos_registers_enabled',
        'value'         =>  $options->get( 'ns_pos_registers_enabled' ),
        'options'         =>  Helper::kvToJsOptions([
            'yes'       =>  __( 'Yes' ),
            'no'        =>  __( 'No' )
        ]),
        'label'         =>  __( 'Enable Cash Registers' ), 
        'type'          =>  'select',
        'description'   =>  __( 'Determine if the POS will support cash registers.' ),
    ], 
];

if ( $options->get( 'ns_pos_registers_enabled' ) === 'yes' ) {
    $cashRegisters[]    =   [
        'label'     =>  __( 'Cashier Idle Counter' ),
        'name'     =>  'ns_pos_idle_counter',
        'value'     =>  $options->get( 'ns_pos_idle_counter' ),
        'options'   =>  Helper::kvToJsOptions([
            'disabled'  =>  __( 'Disabled' ),
            '5min'      =>  __( '5 Minutes' ),
            '10min'     =>  __( '10 Minutes' ),
            '15min'     =>  __( '15 Minutes' ),
            '20min'     =>  __( '20 Minutes' ),
            '30min'     =>  __( '30 Minutes' ),
        ])
    ];

    $cashRegisters[]    =   [
        'label'     =>  __( 'Cash Disbursement' ),
        'name'     =>  'ns_pos_disbursement',
        'value'     =>  $options->get( 'ns_pos_disbursement_enabled' ),
        'options'   =>  Helper::kvToJsOptions([
            'yes'       =>  __( 'Yes' ),
            'no'        =>  __( 'No' ),
        ])
    ];
}

return [
    'label' =>  __( 'Cash Registers' ),
    'fields'    =>  $cashRegisters
];