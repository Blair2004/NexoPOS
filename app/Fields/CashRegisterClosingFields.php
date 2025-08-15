<?php

namespace App\Fields;

use App\Classes\FormInput;
use App\Classes\Hook;
use App\Services\FieldsService;

class CashRegisterClosingFields extends FieldsService
{
    /**
     * The unique identifier of the form
     **/
    const IDENTIFIER = 'ns.cash-registers-closing';

    /**
     * Will ensure the fields are automatically loaded
     **/
    const AUTOLOAD = true;

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
