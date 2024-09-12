<?php

use App\Classes\FormInput;
use App\Crud\TransactionAccountCrud;

return [
    FormInput::multiselect(
        label: __( 'Expense Accounts' ),
        name: 'ns_accounting_expenses_accounts',
        value: ns()->option->get( 'ns_accounting_expenses_accounts' ),
        description: __( 'Assign accounts that are likely to be used for creating direct, scheduled or recurring expenses.' ),
        options: $accounts[ 'expenses' ],
    ),
];
