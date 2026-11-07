<?php

namespace App\Services;

use Illuminate\Http\Request;

abstract class FieldsService
{
    /** Only opt-in authentication form schemas may be retrieved anonymously. */
    public const PUBLIC_ACCESS = false;

    /** Optional permission required to retrieve this form schema. */
    public const REQUIRED_PERMISSION = null;

    /**
     * Authorize field retrieval before constructing model-backed field classes.
     */
    public static function authorizeAccess(): void
    {
        if ( static::PUBLIC_ACCESS ) {
            return;
        }

        abort_unless( request()->user( 'sanctum' ), 401 );

        if ( static::REQUIRED_PERMISSION !== null ) {
            abort_unless( ns()->allowedTo( static::REQUIRED_PERMISSION ), 403 );
        }
    }

    protected $fields = [];

    public function get()
    {
        // Filter fields to include only those with 'show' set to true
        return array_filter( $this->fields, function ( $field ) {
            if ( isset( $field['show'] ) && is_callable( $field['show'] ) ) {
                return $field['show']();
            }

            return true; // Default to true if 'show' is not set
        } );
    }

    /**
     * The unique identifier of the form
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
     *
     * @return array validated input
     */
    public function validate( Request $request )
    {
        $fields = $this->get();
        $rules = collect( $fields )->mapWithKeys( function ( $field ) {
            return [ $field[ 'name' ] => explode( '|', $field[ 'validation' ] ) ];
        } )->toArray();

        return $request->validate( $rules );
    }
}
