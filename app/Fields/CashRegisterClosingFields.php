<?php

namespace App\Fields;

use App\Classes\FormInput;
use App\Classes\Hook;
use App\Services\FieldsService;

class CashRegisterClosingFields extends FieldsService
{
    protected static $identifier = 'ns.cash-registers-closing';

    public function get()
    {
        $fields = Hook::filter( 'ns-cash-register-closing-fields', [
            FormInput::hidden(
                label: __( 'Amount' ),
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
