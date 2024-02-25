<?php

namespace App\Fields;

use App\Models\OrderProduct;
use App\Services\FieldsService;
use App\Services\Helper;

class RefundProductFields extends FieldsService
{
    protected static $identifier = 'ns.refund-product';

    public function get()
    {
        $fields = [
            [
                'label' => __( 'Unit Price' ),
                'description' => __( 'Define what is the unit price of the product.' ),
                'validation' => 'required',
                'name' => 'unit_price',
                'type' => 'number',
            ], [
                'label' => __( 'Condition' ),
                'description' => __( 'Determine in which condition the product is returned.' ),
                'validation' => 'required',
                'name' => 'condition',
                'type' => 'select',
                'options' => Helper::kvToJsOptions( [
                    OrderProduct::CONDITION_DAMAGED => __( 'Damaged' ),
                    OrderProduct::CONDITION_UNSPOILED => __( 'Unspoiled' ),
                ] ),
            ], [
                'label' => __( 'Other Observations' ),
                'description' => __( 'Describe in details the condition of the returned product.' ),
                'name' => 'description',
                'type' => 'textarea',
            ],
        ];

        return $fields;
    }
}
