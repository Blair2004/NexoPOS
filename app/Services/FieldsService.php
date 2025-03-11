<?php

namespace App\Services;

use Illuminate\Http\Request;

class FieldsService
{
    protected $fields = [];

    public function get()
    {
        return $this->fields;
    }

    public static function getIdentifier(): string
    {
        if ( isset( get_called_class()::$identifier ) ) {
            return get_called_class()::$identifier;
        }

        if ( get_called_class()::IDENTIFIER ) {
            return get_called_class()::IDENTIFIER;
        }
    }

    public function validate( Request $request )
    {
        $fields   =   $this->get();
        $rules  =   collect( $fields )->mapWithKeys( function ( $field ) {
            return [ $field[ 'name' ] => explode( '|', $field[ 'validation' ] ) ];
        } )->toArray();

        return $request->validate( $rules );
    }
}
