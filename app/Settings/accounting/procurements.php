<?php

use App\Classes\FormInput;
use App\Classes\SettingForm;
use App\Crud\TransactionAccountCrud;
use App\Services\Helper;

return SettingForm::fields(
    FormInput::searchSelect(
        label: __( 'Paid Procurement Account' ),
        name: 'ns_accounting_procurement_paid_account',
        value: ns()->option->get( 'ns_accounting_procurement_paid_account' ),
        description: __( 'Every paid transaction will be reflected on this account.' ),
        component: 'nsCrudForm',
        props: TransactionAccountCrud::getFormConfig(),
        options: $accounts[ 'assets' ],
    ),
    FormInput::searchSelect(
        label: __( 'Unpaid Procurement Account' ),
        name: 'ns_accounting_procurement_unpaid_account',
        value: ns()->option->get( 'ns_accounting_procurement_unpaid_account' ),
        description: __( 'Every unpaid transaction will be reflected on this account.' ),
        component: 'nsCrudForm',
        props: TransactionAccountCrud::getFormConfig(),
        options: $accounts[ 'liabilities' ],
    ),
);