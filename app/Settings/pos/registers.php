<?php

use App\Models\Expense;
use App\Models\ExpenseCategory;
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
    ], [
        'name'          =>  'ns_pos_cashout_expense_category',
        'value'         =>  $options->get( 'ns_pos_cashout_expense_category' ),
        'options'         =>  Helper::toJsOptions( ExpenseCategory::get(), [ 'id', 'name' ]),
        'label'         =>  __( 'Cash Out Assigned Expense Category' ), 
        'type'          =>  'select',
        'description'   =>  __( 'Every cashout will issue an expense under the selected expense category.' ),
    ], 
];

if ( $options->get( 'ns_pos_registers_enabled' ) === 'yes' ) {
    $cashRegisters[]    =   [
        'label'     =>  __( 'Cashier Idle Counter' ),
        'name'     =>  'ns_pos_idle_counter',
        'type'          =>  'select',
        'value'     =>  $options->get( 'ns_pos_idle_counter' ),
        'options'   =>  Helper::kvToJsOptions([
            'disabled'  =>  __( 'Disabled' ),
            '5min'      =>  __( '5 Minutes' ),
            '10min'     =>  __( '10 Minutes' ),
            '15min'     =>  __( '15 Minutes' ),
            '20min'     =>  __( '20 Minutes' ),
            '30min'     =>  __( '30 Minutes' ),
        ]),
        'description'   =>  __( 'Selected after how many minutes the system will set the cashier as idle.' ),
    ];

    $cashRegisters[]    =   [
        'label'         =>  __( 'Cash Disbursement' ),
        'name'          =>  'ns_pos_disbursement',
        'type'          =>  'select',
        'value'         =>  $options->get( 'ns_pos_disbursement_enabled' ),
        'description'   =>  __( 'Allow cash disbursement by the cashier.' ),
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