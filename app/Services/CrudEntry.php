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

    public function jsonSerialize()
    {
        return $this->values;
    }

    /**
     * @deprecated
     */
    public function addAction( $identifier, $action )
    {
        $this->values[ '$actions' ][ $identifier ] = $action;
    }

    public function action( $label, $identifier, $url = 'javascript:void(0)', $confirm = null, $type = 'GOTO' )
    {
        $this->values[ '$actions' ][ $identifier ]      =   compact( 'label', 'identifier', 'url', 'confirm', 'type' );
    }

    public function removeAction( $identifier )
    {
        unset( $this->values[ '$actions' ][ $identifier ] );
    }

    public function toArray()
    {
        return $this->values;
    }
}
