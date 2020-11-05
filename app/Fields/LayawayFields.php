<?php
namespace App\Fields;

use App\Services\FieldsService;
use App\Services\Helper;

class LayawayFields extends FieldsService
{
    public function get()
    {
        $fields     =   [
            [
                'label'         =>  __( 'Payment Date' ),
                'description'   =>  __( 'determine the expected payment date.' ),
                'validation'    =>  'required',
                'type'          =>  'date',
                'name'          =>  'expected_payment_date',
            ], [
                'label'         =>  __( 'Installments' ),
                'description'   =>  __( 'Define the installments for the current order.' ),
                'name'          =>  'total_installments',
                'type'          =>  'number',
            ]
        ];
        
        return $fields;
    }
}