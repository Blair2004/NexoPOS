<?php

namespace App\Fields;

use App\Classes\FormInput;
use App\Classes\Hook;
use App\Models\TransactionAccount;
use App\Services\FieldsService;
use App\Services\Helper;

class CashRegisterCashingFields extends FieldsService
{
    protected static $identifier = 'ns.cash-registers-cashing';

    public function get()
    {
        $accounts = TransactionAccount::whereIn( 'id', ns()->option->get( 'ns_accounting_cashing_accounts', [] ) )->get();

        $fields = Hook::filter( 'ns-cash-register-cashing-fields', [
            FormInput::hidden(
                label: __( 'Amount' ),
                description: __( 'define the amount of the transaction.' ),
                name: 'amount',
            ),
            FormInput::searchSelect(
                label: __( 'Transaction Account' ),
                name: 'transaction_account_id',
                description: __( 'Select the account to proceed.' ),
                validation: 'required',
                value: ns()->option->get( 'ns_accounting_default_cashing_account' ),
                options: Helper::toJsOptions( $accounts, [ 'id', 'name' ] )
            ),
            FormInput::textarea(
                label: __( 'Description' ),
                description: __( 'Further observation while proceeding.' ),
                name: 'description',
            ),
        ] );

        return $fields;
    }
}
