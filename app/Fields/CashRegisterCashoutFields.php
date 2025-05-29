<?php

namespace App\Fields;

use App\Classes\FormInput;
use App\Classes\Hook;
use App\Models\TransactionAccount;
use App\Services\FieldsService;
use App\Services\Helper;

class CashRegisterCashoutFields extends FieldsService
{
    protected static $identifier = 'ns.cash-registers-cashout';

    public function get()
    {
        $fields = Hook::filter( 'ns-cash-register-cashout-fields', [
            FormInput::hidden(
                label: __( 'Amount' ),
                description: __( 'define the amount of the transaction.' ),
                name: 'amount',
            ),
            FormInput::textarea(
                label : __( 'Description' ),
                name: 'description'
            ),
        ] );

        return $fields;
    }
}
