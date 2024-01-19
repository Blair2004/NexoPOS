<?php

use App\Crud\TransactionAccountCrud;
use App\Models\TransactionAccount;
use App\Services\Helper;

$transactions = TransactionAccount::get();

return [
    'label' => __('General'),
    'fields' => [
        [
            'label' => __('Procurement Cash Flow Account'),
            'name' => 'ns_procurement_cashflow_account',
            'value' => ns()->option->get('ns_procurement_cashflow_account'),
            'description' => __('Every procurement will be added to the selected transaction account'),
            'component' => 'nsCrudForm',
            'props' => TransactionAccountCrud::getFormConfig(),
            'options' => Helper::toJsOptions($transactions, [ 'id', 'name' ]),
            'type' => 'search-select',
        ], [
            'label' => __('Sale Cash Flow Account'),
            'name' => 'ns_sales_cashflow_account',
            'value' => ns()->option->get('ns_sales_cashflow_account'),
            'description' => __('Every sales will be added to the selected transaction account'),
            'component' => 'nsCrudForm',
            'props' => TransactionAccountCrud::getFormConfig(),
            'options' => Helper::toJsOptions($transactions, [ 'id', 'name' ]),
            'type' => 'search-select',
        ], [
            'label' => __('Customer Credit Account (crediting)'),
            'name' => 'ns_customer_crediting_cashflow_account',
            'value' => ns()->option->get('ns_customer_crediting_cashflow_account'),
            'description' => __('Every customer credit will be added to the selected transaction account'),
            'component' => 'nsCrudForm',
            'props' => TransactionAccountCrud::getFormConfig(),
            'options' => Helper::toJsOptions($transactions, [ 'id', 'name' ]),
            'type' => 'search-select',
        ], [
            'label' => __('Customer Credit Account (debitting)'),
            'name' => 'ns_customer_debitting_cashflow_account',
            'value' => ns()->option->get('ns_customer_debitting_cashflow_account'),
            'description' => __('Every customer credit removed will be added to the selected transaction account'),
            'component' => 'nsCrudForm',
            'props' => TransactionAccountCrud::getFormConfig(),
            'options' => Helper::toJsOptions($transactions, [ 'id', 'name' ]),
            'type' => 'search-select',
        ], [
            'label' => __('Sales Refunds Account'),
            'name' => 'ns_sales_refunds_account',
            'value' => ns()->option->get('ns_sales_refunds_account'),
            'description' => __('Sales refunds will be attached to this transaction account'),
            'component' => 'nsCrudForm',
            'props' => TransactionAccountCrud::getFormConfig(),
            'options' => Helper::toJsOptions($transactions, [ 'id', 'name' ]),
            'type' => 'search-select',
        ], [
            'label' => __('Stock Return Account (Spoiled Items)'),
            'name' => 'ns_stock_return_spoiled_account',
            'value' => ns()->option->get('ns_stock_return_spoiled_account'),
            'description' => __('Stock return for spoiled items will be attached to this account'),
            'component' => 'nsCrudForm',
            'props' => TransactionAccountCrud::getFormConfig(),
            'options' => Helper::toJsOptions($transactions, [ 'id', 'name' ]),
            'type' => 'search-select',
        ], [
            'label' => __('Disbursement (cash register)'),
            'name' => 'ns_disbursement_cash_register_account',
            'value' => ns()->option->get('ns_disbursement_cash_register_account'),
            'component' => 'nsCrudForm',
            'props' => TransactionAccountCrud::getFormConfig(),
            'description' => __('Transaction account for all cash disbursement.'),
            'options' => Helper::toJsOptions($transactions, [ 'id', 'name' ]),
            'type' => 'search-select',
        ], [
            'label' => __('Liabilities'),
            'name' => 'ns_liabilities_account',
            'value' => ns()->option->get('ns_liabilities_account'),
            'description' => __('Transaction account for all liabilities.'),
            'component' => 'nsCrudForm',
            'props' => TransactionAccountCrud::getFormConfig(),
            'options' => Helper::toJsOptions($transactions, [ 'id', 'name' ]),
            'type' => 'search-select',
        ],
    ],
];
