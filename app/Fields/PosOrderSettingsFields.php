<?php

namespace App\Fields;

use App\Services\FieldsService;

class PosOrderSettingsFields extends FieldsService
{
    /**
     * The unique identifier of the form
     **/
    const IDENTIFIER = 'ns.pos-order-settings';

    /**
     * Will ensure the fields are automatically
     * loaded
     **/
    const AUTOLOAD = true;

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
