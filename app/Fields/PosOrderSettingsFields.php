<?php

namespace App\Fields;

use App\Services\FieldsService;

class PosOrderSettingsFields extends FieldsService
{
    protected static $identifier = 'ns.pos-order-settings';

    public function get()
    {
        $fields = [
            [
                'label' => __( 'Name' ),
                'description' => __( 'Define the order name.' ),
                'validation' => 'required',
                'name' => 'title',
                'type' => 'text',
            ], [
                'label' => __( 'Created At' ),
                'description' => __( 'Define the date of creation of the order.' ),
                'name' => 'created_at',
                'type' => 'date',
            ],
        ];

        return $fields;
    }
}
