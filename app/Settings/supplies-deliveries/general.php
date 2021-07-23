<?php

use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\ExpenseCategory;
use App\Services\Helper;

return [
    'label'     =>  __( 'Supplies' ),
    'fields'    =>  [
        [
            'type'  =>  'switch',
            'label' =>  __( 'Generate Expenses' ),
            'value'         =>  ( int ) ns()->option->get( 'ns_supplies_create_expenses' ),
            'description'   =>  __( 'Whether an expense should be generated for paid procurements.' ),
            'name'          =>  'ns_supplies_create_expenses',
            'options'       =>  Helper::kvToJsOptions([ __( 'No' ), __( 'Yes' ) ])
        ], [
            'type'  =>  'select',
            'label' =>  __( 'Expenses Category' ),
            'value'         =>  ns()->option->get( 'ns_supplies_expense_category' ),
            'description'   =>  __( 'Each paid procurement will create an expense automatically. Select to which category the expense should be assigned to.' ),
            'name'          =>  'ns_supplies_expense_category',
            'options'       =>  Helper::toJsOptions( ExpenseCategory::get(), [ 'id', 'name' ]),
        ]
    ]
];