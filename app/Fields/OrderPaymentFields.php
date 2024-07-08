<?php

namespace App\Fields;

use App\Classes\FormInput;
use App\Models\PaymentType;
use App\Models\Register;
use App\Services\FieldsService;
use App\Services\Helper;

class OrderPaymentFields extends FieldsService
{
    protected static $identifier = 'ns.order-payments';

    public function __construct()
    {
        // ...
    }

    public function get()
    {
        $fields = [];

        $fields[] = FormInput::select(
            label: __( 'Select Payment' ),
            name: 'identifier',
            options: collect( PaymentType::active()->get() )->map( function ( $payment ) {
                $payment[ 'value' ] = $payment[ 'identifier' ];

                return $payment;
            } ),
            description: __( 'choose the payment type.' ),
            validation: 'required',
        );

        if ( ns()->option->get( 'ns_pos_registers_enabled', 'no' ) === 'yes' ) {
            $registers = Register::opened()->get();
            $fields[] = FormInput::select(
                label: __( 'Select Register' ),
                name: 'register_id',
                disabled: $registers->isEmpty(),
                options: Helper::toJsOptions( $registers, [ 'id', 'name' ] ),
                description: __( 'Choose a register.' ),
                validation: 'required',
            );
        }

        return $fields;
    }
}
