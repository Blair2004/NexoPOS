<?php

use App\Models\Provider;
use App\Services\Helper;

return [
    'label' =>  __( 'Procurement' ),
    'fields'    =>  [
        [
            'type'  =>  'text',
            'name'  =>  'invoice_reference',
            'value' =>  $procurement->invoice_reference ?? '',
            'label' =>  __( 'Invoice Number' ),
            'description'   =>  __( 'If the procurement has been issued outside of NexoPOS, please provide a unique reference.' )
        ], [
            'type'  =>  'date',
            'name'  =>  'delivery_time',
            'value' =>  $procurement->delivery_time ?? '',
            'label' =>  __( 'Delivery Time' ),
            'description'   =>  __( 'If the procurement has to be delivered at a specific time, define the moment here.' )
        ], [
            'type'  =>  'date',
            'name'  =>  'invoice_date',
            'value' =>  $procurement->invoice_date ?? '',
            'label' =>  __( 'Invoice Date' ),
            'description'   =>  __( 'If you would like to define a custom invoice date.' )
        ], [
            'type'          =>  'switch',
            'name'          =>  'automatic_approval',
            'value' =>  $procurement->automatic_approval ?? '',
            'options'       =>  Helper::kvToJsOptions([
                0   =>  __( 'No' ),
                1   =>  __( 'Yes' ),
            ]),
            'label'         =>  __( 'Automatic Approval' ),
            'description'   =>  __( 'Determine if the procurement should be marked automatically as approved once the Delivery Time occurs.' )
        ], [
            'type'          =>  'select',
            'name'          =>  'delivery_status',
            'value' =>  $procurement->delivery_status ?? '',
            'validation'    =>  'required',
            'options'           =>  Helper::kvToJsOptions([
                'pending'       =>  __( 'Pending' ),
                'delivered'     =>  __( 'Delivered' ),
            ]),
            'label'         =>  __( 'Delivery Status' ),
            'description'   =>  __( 'Determine what is the actual value of the procurement. Once "Delivered" the status can\'t be changed, and the stock will be updated.' )
        ], [
            'type'          =>  'select',
            'name'          =>  'payment_status',
            'value'         =>  $procurement->payment_status ?? '',
            'validation'    =>  'required',
            'options'           =>  Helper::kvToJsOptions([
                'unpaid'    =>  __( 'Unpaid' ),
                'paid'      =>  __( 'Paid' ),
            ]),
            'label'         =>  __( 'Payment Status' ),
            'description'   =>  __( 'Determine what is the actual payment status of the procurement.' )
        ], [
            'type'          =>  'select',
            'name'          =>  'provider_id',
            'value' =>  $procurement->provider_id ?? '',
            'validation'    =>  'required',
            'options'       =>  Helper::toJsOptions( Provider::get(), [ 'id', 'name' ]),
            'label'         =>  __( 'Provider' ),
            'description'   =>  __( 'Determine what is the actual provider of the current procurement.' )
        ]
    ]
];