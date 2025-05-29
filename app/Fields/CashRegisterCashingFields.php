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
        $fields = Hook::filter( 'ns-cash-register-cashing-fields', [
            FormInput::hidden(
                label: __( 'Amount' ),
                description: __( 'define the amount of the transaction.' ),
                name: 'amount',
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
