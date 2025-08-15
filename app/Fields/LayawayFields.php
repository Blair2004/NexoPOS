<?php

namespace App\Fields;

use App\Services\FieldsService;

class LayawayFields extends FieldsService
{
    /**
     * The unique identifier of the form
     **/
    const IDENTIFIER = 'ns.layaway';

    /**
     * Will ensure the fields are automatically loaded
     **/
    const AUTOLOAD = true;

    public function get()
    {
        $fields = [
            [
                'label' => __( 'Installments' ),
                'description' => __( 'Define the installments for the current order.' ),
                'name' => 'total_instalments',
                'type' => 'number',
            ],
        ];

        return $fields;
    }
}
