<?php
namespace App\Fields;

use App\Services\FieldsService;
use App\Classes\Form;
use App\Services\Helper;
use App\Classes\FormInput;
use App\Models\PaymentType;

class OrderDeliveryProofFields extends FieldsService
{
    /**
     * The unique identifier of the form
     * @var string
     */
    const IDENTIFIER = 'ns.order-delivery-proof';

    /**
     * Will ensure the fields are automatically 
     * loaded
     * @var bool
     */
    const AUTOLOAD = true;

    public function get()
    {
        return Form::fields(
            FormInput::switch(
                label: __( 'Is Delivered' ),
                name: 'is_delivered',
                description: __( 'Is the order delivered?' ),
                validation: 'required',
                options: Helper::kvToJsOptions([ __( 'No' ), __( 'Yes' ) ]),
            ),
            FormInput::media(
                label: __( 'Delivery Proof' ),
                name: 'delivery_proof',
                description: __( 'Upload the delivery proof' ),
            ),
            FormInput::switch(
                label: __( 'Paid on Delivery' ),
                name: 'paid_on_delivery',
                description: __( 'Is the order paid on delivery?' ),
                validation: 'required',
                options: Helper::kvToJsOptions([ __( 'No' ), __( 'Yes' ) ]),
            ),
            FormInput::select(
                label: __( 'Payment Method' ),
                name: 'payment_method',
                description: __( 'Select the payment method' ),
                options: Helper::toJsOptions( PaymentType::get(),  [ 'identifier', 'label' ] ),
            ),
            FormInput::textarea(
                label: __( 'Note' ),
                name: 'note',
                description: __( 'Note' )
            ),
        );
    }
}