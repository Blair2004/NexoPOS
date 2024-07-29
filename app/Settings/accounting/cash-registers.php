<?php

use App\Classes\FormInput;
use App\Classes\SettingForm;
use App\Crud\TransactionAccountCrud;

return SettingForm::fields(
    FormInput::multiselect(
        label: __( 'Allowed Cash In Account' ),
        name: 'ns_accounting_cashing_accounts',
        description: __( 'Define on which accounts cashing transactions are allowed' ),
        options: [],
        value: ns()->option->get( 'ns_accounting_cashing_accounts' ),
    ),                        
    FormInput::searchSelect(
        label: __( 'Default Cash In Account' ),
        name: 'ns_accounting_default_cashing_account',
        description: __( 'Select the account where cashing transactions will be posted' ),
        options: [],
        component: 'nsCrudForm',
        props: TransactionAccountCrud::getFormConfig(),
        value: ns()->option->get( 'ns_accounting_default_cashing_account' ),
    ),
    FormInput::multiselect(
        label: __( 'Allowed Cash Out Account' ),
        name: 'ns_accounting_cashout_accounts',
        description: __( 'Define on which accounts cashout transactions are allowed' ),
        options: [],
        value: ns()->option->get( 'ns_accounting_cashout_accounts' ),
    ),
    FormInput::searchSelect(
        label: __( 'Default Cash Out Account' ),
        name: 'ns_accounting_default_cashout_account',
        description: __( 'Select the account where cash out transactions will be posted' ),
        options: [],
        component: 'nsCrudForm',
        props: TransactionAccountCrud::getFormConfig(),
        value: ns()->option->get( 'ns_accounting_default_cashout_account' ),
    ),
    FormInput::searchSelect(
        label: __( 'Opening Float Account' ),
        name: 'ns_accounting_opening_float_account',
        description: __( 'Select the account from which the opening float will be taken' ),
        options: [],
        component: 'nsCrudForm',
        props: TransactionAccountCrud::getFormConfig(),
        value: ns()->option->get( 'ns_accounting_opening_float_account' ),
    ),
    FormInput::searchSelect(
        label: __( 'Closing Float Account' ),
        name: 'ns_accounting_closing_float_account',
        description: __( 'Select the account from which the closing float will be taken' ),
        options: [],
        component: 'nsCrudForm',
        props: TransactionAccountCrud::getFormConfig(),
        value: ns()->option->get( 'ns_accounting_closing_float_account' ),
    )
);