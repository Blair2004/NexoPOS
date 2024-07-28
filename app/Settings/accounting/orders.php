<?php

use App\Classes\FormInput;
use App\Classes\SettingForm;
use App\Crud\TransactionAccountCrud;
use App\Models\TransactionAccount;
use App\Services\Helper;

$props = TransactionAccountCrud::getFormConfig();
$options = Helper::toJsOptions( TransactionAccount::get(), [ 'id', 'name' ] );

return SettingForm::fields(
    FormInput::searchSelect(
        label: __( 'Revenues Account' ),
        name: 'accounting_orders_revenues_account',
        value: ns()->option->get( 'accounting_orders_revenues_account' ),
        description: __( 'Every order revenue will be added to the selected transaction account' ),
        component: 'nsCrudForm',
        props: $props,
        options: $options,
    ),
    FormInput::searchSelect(
        label: __( 'Order Cash Account' ),
        name: 'accounting_orders_cash_account',
        value: ns()->option->get( 'accounting_orders_cash_account' ),
        description: __( 'Every order cash payment will be reflected on this account' ),
        component: 'nsCrudForm',
        props: $props,
        options: $options,
    ),
    FormInput::searchSelect(
        label: __( 'Unpaid Order Account' ),
        name: 'accounting_orders_receivable_account',
        value: ns()->option->get( 'accounting_orders_receivable_account' ),
        description: __( 'Every unpaid orders will be recorded on this account.' ),
        component: 'nsCrudForm',
        props: $props,
        options: $options,
    ),
);