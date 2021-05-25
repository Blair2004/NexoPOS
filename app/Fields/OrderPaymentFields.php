<?php
namespace App\Fields;

use App\Models\PaymentType;
use App\Services\FieldsService;
use App\Services\Helper;

class OrderPaymentFields extends FieldsService
{
    public function get()
    {
        $fields     =   [
            [
                'label'         =>  __( 'Select Payment' ),
                'description'   =>  __( 'choose the payment type.' ),
                'validation'    =>  'required',
                'name'          =>  'identifier',
                'type'          =>  'select',
                'options'       =>  collect( PaymentType::active()->get() )->map( function( $payment ) {
                    $payment[ 'value' ]     =   $payment[ 'identifier' ];
                    return $payment;
                })
            ], 
        ];
        
        return $fields;
    }
}