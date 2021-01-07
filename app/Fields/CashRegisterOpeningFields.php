<?php
namespace App\Fields;

use App\Classes\Hook;
use App\Services\FieldsService;

class CashRegisterOpeningFields extends FieldsService
{
    public function get()
    {
        $fields     =   Hook::filter( 'ns-login-fields', [
            [
                'label'         =>  __( 'Amount' ),
                'description'   =>  __( 'define the amount of the transaction.' ),
                'validation'    =>  'required',
                'name'          =>  'amount',
                'type'          =>  'number',
            ], [
                'label'         =>  __( 'Description' ),
                'description'   =>  __( 'Further observation while proceeding.' ),
                'name'          =>  'description',
                'type'          =>  'number',
            ],
        ]);
        
        return $fields;
    }
}