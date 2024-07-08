<?php

namespace App\Services;

use JsonSerializable;

class CrudEntry implements JsonSerializable
{
    private $original;

    public $values;

    public $__raw;

    public function __construct( $params )
    {
        $this->original = $params;
        $this->values = $params;

        $this->{ '$checked' } = false;
        $this->{ '$toggled' } = false;
        $this->{ '$id' } = $params[ 'id' ];
    }

    public function addClass( $class )
    {
        $classes = explode( ' ', $this->{ '$cssClass' } ?? '' );
        $classes[] = $class;

        $this->{ '$cssClass' } = implode( ' ', $classes );
    }

    public function __get( $index )
    {
        return $this->values[ $index ] ?? null;
    }

    public function __set( $index, $value )
    {
        $this->values[ $index ] = $value;
    }

    public function __isset( $index )
    {
        return array_key_exists( $index, $this->values );
    }

    public function __unset( $index )
    {
        unset( $this->values[ $index ] );
    }

    public function getOriginalValue( $index )
    {
        return $this->original[ $index ];
    }

    public function getRawValue( $index )
    {
        return $this->original[ $index ] ?? null;
    }

    public function jsonSerialize()
    {
        $this->filterActions();

        return $this->values;
    }

    public function action( $label, $identifier, $url = 'javascript:void(0)', $confirm = null, $type = 'GOTO', $permissions = [] )
    {
        $this->values[ '$actions' ][ $identifier ] = compact( 'label', 'identifier', 'url', 'confirm', 'type', 'permissions' );
    }

    public function removeAction( $identifier )
    {
        unset( $this->values[ '$actions' ][ $identifier ] );
    }

    private function filterActions()
    {
        /**
         * if the $actions are set, and permissions are provided, we'll filter the actions
         * to restrict it to allowed users.
         */
        if ( isset( $this->values[ '$actions' ] ) ) {
            $this->values[ '$actions' ] = collect( $this->values[ '$actions' ] )->filter( function ( $action ) {
                return ( isset( $action[ 'permissions' ] ) && count( $action[ 'permissions' ] ) > 0 )
                    ? ns()->allowedTo( $action[ 'permissions' ] ) : true;
            } )->toArray();
        }
    }

    public function toArray()
    {
        $this->filterActions();

        return $this->values;
    }
}
