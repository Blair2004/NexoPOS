<?php
namespace App\Fields;

use App\Classes\Hook;
use App\Services\FieldsService;

class CashRegisterCashingFields extends FieldsService
{
    public function get()
    {
        $fields     =   Hook::filter( 'ns-cash-register-cashing-fields', [
            [
                'label'         =>  __( 'Amount' ),
                'description'   =>  __( 'define the amount of the transaction.' ),
                'validation'    =>  'required',
                'name'          =>  'amount',
                'type'          =>  'hidden',
            ], [
                'label'         =>  __( 'Description' ),
                'description'   =>  __( 'Further observation while proceeding.' ),
                'name'          =>  'description',
                'type'          =>  'textarea',
            ],
        ]);
        
        return $fields;
    }
}