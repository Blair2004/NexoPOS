<?php

namespace App\Services;

use Illuminate\Http\Request;

abstract class FieldsService
{
    protected $fields = [];

    public function get()
    {
        return $this->fields;
    }

    /**
     * The unique identifier of the form
     * @return string
     */
    public static function getIdentifier(): string
    {
        if ( isset( get_called_class()::$identifier ) ) {
            return get_called_class()::$identifier;
        }

        if ( get_called_class()::IDENTIFIER ) {
            return get_called_class()::IDENTIFIER;
        }
    }

    /**
     * Validate the request input againts the current defined fields.
     * @param Request $request
     * @return array validated input
     */
    public function validate( Request $request )
    {
        $fields   =   $this->get();
        $rules  =   collect( $fields )->mapWithKeys( function ( $field ) {
            return [ $field[ 'name' ] => explode( '|', $field[ 'validation' ] ) ];
        } )->toArray();

        return $request->validate( $rules );
    }
}
