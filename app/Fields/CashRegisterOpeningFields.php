<?php

namespace App\Fields;

use App\Classes\FormInput;
use App\Classes\Hook;
use App\Services\FieldsService;

class CashRegisterOpeningFields extends FieldsService
{
    protected static $identifier = 'ns.cash-registers-opening';

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
