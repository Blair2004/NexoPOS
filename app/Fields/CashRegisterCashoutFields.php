<?php

namespace App\Fields;

use App\Classes\FormInput;
use App\Classes\Hook;
use App\Services\FieldsService;

class CashRegisterCashoutFields extends FieldsService
{
    /**
     * The unique identifier of the form
     **/
    const IDENTIFIER = 'ns.cash-registers-cashout';

    /**
     * Will ensure the fields are automatically loaded
     **/
    const AUTOLOAD = true;

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
