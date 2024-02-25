<?php

namespace App\Fields;

use App\Services\FieldsService;

class LayawayFields extends FieldsService
{
    protected static $identifier = 'ns.layaway';

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
