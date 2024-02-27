<?php

namespace App\Fields;

use App\Models\Procurement;
use App\Services\FieldsService;

class ProcurementFields extends FieldsService
{
    protected static $identifier = 'ns.procurement-fields';

    public function get( ?Procurement $model = null )
    {
        $name = new \stdClass;
        $name->name = 'name';
        $name->label = __( 'Name' );
        $name->validation = 'required|min:5';
        $name->description = __( 'Provide the procurement name.' );

        $description = new \stdClass;
        $description->name = 'description';
        $description->label = __( 'Description' );
        $description->validation = '';
        $description->description = __( 'Describe the procurement.' );

        $provider_id = new \stdClass;
        $provider_id->name = 'provider_id';
        $provider_id->label = __( 'Unit Group' );
        $provider_id->validation = 'required';
        $provider_id->description = __( 'Define the provider.' );

        /**
         * let's populate the value
         * using a clear method
         */
        return collect( [ $name, $description, $provider_id ] )->map( function ( $field ) use ( $model ) {
            $field->value = $this->__getValue( $model, $field->name );

            return $field;
        } )->toArray();
    }

    private function __getValue( $model, $field )
    {
        if ( $model instanceof Procurement ) {
            return $model->$field ?? '';
        }

        return '';
    }
}
