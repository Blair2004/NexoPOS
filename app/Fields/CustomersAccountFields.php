<?php

namespace App\Fields;

use App\Services\FieldsService;
use App\Services\Helper;

class CustomersAccountFields extends FieldsService
{
    protected static $identifier = 'ns.customers-account';

    public function get()
    {
        $fields = [
            [
                'label' => __( 'Type' ),
                'description' => __( 'determine what is the transaction type.' ),
                'validation' => 'required',
                'name' => 'operation',
                'type' => 'select',
                'options' => Helper::kvToJsOptions( [
                    'add' => __( 'Add' ),
                    'deduct' => __( 'Deduct' ),
                ] ),
            ], [
                'label' => __( 'Amount' ),
                'description' => __( 'Determine the amount of the transaction.' ),
                'name' => 'amount',
                'type' => 'number',
            ], [
                'label' => __( 'Description' ),
                'description' => __( 'Further details about the transaction.' ),
                'name' => 'description',
                'type' => 'textarea',
            ],
        ];

        return $fields;
    }
}
