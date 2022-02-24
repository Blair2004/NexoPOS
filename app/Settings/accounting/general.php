<?php

use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\ExpenseCategory;
use App\Services\Helper;

$expenses   =   ExpenseCategory::get();

return [
    'label'     =>  __( 'General' ),
    'fields'    =>  [
        [
            'label'     =>  __( 'Procurement Cash Flow Account' ),
            'name'      =>  'ns_procurement_cashflow_account',
            'value'     =>  ns()->option->get( 'ns_procurement_cashflow_account' ),
            'description'   =>  __( 'Every procurement will be added to the selected cash flow account' ),
            'options'       =>  Helper::toJsOptions( $expenses, [ 'id', 'name' ]),
            'type'          =>  'select',
        ], [
            'label'     =>  __( 'Sale Cash Flow Account' ),
            'name'      =>  'ns_sales_cashflow_account',
            'value'     =>  ns()->option->get( 'ns_sales_cashflow_account' ),
            'description'   =>  __( 'Every sales will be added to the selected cash flow account' ),
            'options'       =>  Helper::toJsOptions( $expenses, [ 'id', 'name' ]),
            'type'          =>  'select',
        ], [
            'label'     =>  __( 'Customer Credit Account (crediting)' ),
            'name'      =>  'ns_customer_crediting_cashflow_account',
            'value'     =>  ns()->option->get( 'ns_customer_crediting_cashflow_account' ),
            'description'   =>  __( 'Every customer credit will be added to the selected cash flow account' ),
            'options'       =>  Helper::toJsOptions( $expenses, [ 'id', 'name' ]),
            'type'          =>  'select',
        ], [
            'label'     =>  __( 'Customer Credit Account (debitting)' ),
            'name'      =>  'ns_customer_debitting_cashflow_account',
            'value'     =>  ns()->option->get( 'ns_customer_debitting_cashflow_account' ),
            'description'   =>  __( 'Every customer credit removed will be added to the selected cash flow account' ),
            'options'       =>  Helper::toJsOptions( $expenses, [ 'id', 'name' ]),
            'type'          =>  'select',
        ], [
            'label'     =>  __( 'Sales Refunds Account' ),
            'name'      =>  'ns_sales_refunds_account',
            'value'     =>  ns()->option->get( 'ns_sales_refunds_account' ),
            'description'   =>  __( 'Sales refunds will be attached to this cash flow account' ),
            'options'       =>  Helper::toJsOptions( $expenses, [ 'id', 'name' ]),
            'type'          =>  'select',
        ], [
            'label'     =>  __( 'Stock Return Account (Spoiled Items)' ),
            'name'      =>  'ns_stock_return_spoiled_account',
            'value'     =>  ns()->option->get( 'ns_stock_return_spoiled_account' ),
            'description'   =>  __( 'Stock return for spoiled items will be attached to this account' ),
            'options'       =>  Helper::toJsOptions( $expenses, [ 'id', 'name' ]),
            'type'          =>  'select',
        ], [
            'label'     =>  __( 'Cash Register Cash-In Account' ),
            'name'      =>  'ns_cashregister_cashin_cashflow_account',
            'value'     =>  ns()->option->get( 'ns_cashregister_cashin_cashflow_account' ),
            'description'   =>  __( 'Cash Register cash-in will be added to the cash flow account' ),
            'options'       =>  Helper::toJsOptions( $expenses, [ 'id', 'name' ]),
            'type'          =>  'select',
        ], [
            'label'     =>  __( 'Cash Register Cash-Out Account' ),
            'name'      =>  'ns_cashregister_cashout_cashflow_account',
            'value'     =>  ns()->option->get( 'ns_cashregister_cashout_cashflow_account' ),
            'description'   =>  __( 'Cash Register cash-out will be added to the cash flow account' ),
            'options'       =>  Helper::toJsOptions( $expenses, [ 'id', 'name' ]),
            'type'          =>  'select',
        ]
    ]
];