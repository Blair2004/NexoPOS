<?php
namespace App\Fields;

use App\Services\FieldsService;
use App\Classes\Form;
use App\Services\Helper;
use App\Classes\FormInput;
use App\Models\Order;
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
        // let's get the route paramter "identifier"
        $identifier = request()->route('identifier');

        $order = Order::findOrFail( $identifier );

        $fields = Form::fields(
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
            FormInput::textarea(
                label: __( 'Note' ),
                name: 'note',
                description: __( 'Note' )
            ),
        );

        // if the order is already paid, we might not ask to provide the payment options
        if ( in_array( $order->payment_status, [ Order::PAYMENT_DUE, Order::PAYMENT_HOLD, Order::PAYMENT_UNPAID ] ) ) {
            $paymentFields = [
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
            ];

            // Insert payment fields at index 2 (between delivery proof and note)
            array_splice( $fields, 2, 0, $paymentFields );
        }

        return $fields;
    }
}