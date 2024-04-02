<?php

namespace App\Fields;

use App\Models\Unit;
use App\Models\UnitGroup;
use App\Services\FieldsService;

class UnitsFields extends FieldsService
{
    protected static $identifier = 'ns.units-fields';

    public function get( ?Unit $model = null )
    {
        $name = new \stdClass;
        $name->name = 'name';
        $name->label = __( 'Unit Group Name' );
        $name->validation = 'required';
        $name->description = __( 'Provide a unit name to the unit.' );

        $description = new \stdClass;
        $description->name = 'description';
        $description->label = __( 'Description' );
        $description->validation = '';
        $description->description = __( 'Describe the current unit.' );

        $group_id = new \stdClass;
        $group_id->name = 'group_id';
        $group_id->label = __( 'Unit Group' );
        $group_id->validation = 'required';
        $group_id->description = __( 'assign the current unit to a group.' );

        $value = new \stdClass;
        $value->name = 'value';
        $value->label = __( 'Value' );
        $value->validation = 'required';
        $value->description = __( 'define the unit value.' );

        $base_unit = new \stdClass;
        $base_unit->name = 'base_unit';
        $base_unit->label = __( 'Value' );
        $base_unit->validation = 'boolean|required';
        $base_unit->description = __( 'define the unit value.' );

        /**
         * let's populate the value
         * using a clear method
         */
        return collect( [ $name, $description, $group_id, $value, $base_unit ] )->map( function ( $field ) use ( $model ) {
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
