<?php

namespace App\Fields;

use App\Models\UnitGroup;
use App\Services\FieldsService;

class UnitsGroupsFields extends FieldsService
{
    protected static $identifier = 'ns.units-group-fields';

    public function get( ?UnitGroup $model = null )
    {
        $name = new \stdClass;
        $name->name = 'name';
        $name->label = __( 'Unit Group Name' );
        $name->validation = 'required';
        $name->description = __( 'Provide a unit name to the units group.' );

        $description = new \stdClass;
        $description->name = 'description';
        $description->label = __( 'Description' );
        $description->validation = '';
        $description->description = __( 'Describe the current unit group.' );

        /**
         * let's populate the value
         * using a clear method
         */
        return collect( [ $name, $description ] )->map( function ( $field ) use ( $model ) {
            $field->value = $this->__getValue( $model, $field->name );

            return $field;
        } )->toArray();
    }

    private function __getValue( $model, $field )
    {
        if ( $model instanceof UnitGroup ) {
            return $model->$field ?? '';
        }

        return '';
    }
}
