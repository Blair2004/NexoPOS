<?php
namespace App\Fields;

use App\Models\Order;
use App\Services\FieldsService;
use App\Services\Helper;

class LayawayFields extends FieldsService
{
    public function get()
    {
        $fields     =   [
            [
                'label'         =>  __( 'Installments' ),
                'description'   =>  __( 'Define the installments for the current order.' ),
                'name'          =>  'total_instalments',
                'type'          =>  'number',
            ]
        ];
        
        return $fields;
    }
}