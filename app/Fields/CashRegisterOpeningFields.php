<?php

namespace App\Fields;

use App\Classes\FormInput;
use App\Classes\Hook;
use App\Services\FieldsService;

class CashRegisterOpeningFields extends FieldsService
{
    /**
     * The unique identifier of the form
     **/
    const IDENTIFIER = 'ns.cash-registers-opening';

    /**
     * Will ensure the fields are automatically loaded
     **/
    const AUTOLOAD = true;

    public function get()
    {
        $fields = Hook::filter( 'ns-cash-register-open-fields', [
            FormInput::hidden(
                name: 'amount',
                label: __( 'Amount' ),
            ),
            FormInput::textarea(
                name: 'description',
                label: __( 'Description' ),
                description: __( 'Further observation while proceeding.' ),
            ),
        ] );

        return $fields;
    }
}
