<?php
namespace App\Services;

use JsonSerializable;

class CrudEntry implements JsonSerializable
{
    private $original;
    public $values;

    public function __construct( $params )
    {
        $this->original     =   $params;
        $this->values       =   $params;
        
        $this->{ '$checked' }       =   false;
        $this->{ '$toggled' }       =   false;
        $this->{ '$id' }            =   $params[ 'id' ];
    }

    public function __get( $index ) 
    {
        return $this->values[ $index ] ?? null;
    }

    public function __set( $index, $value )
    {
        $this->values[ $index ]     =   $value;
    }

    public function getOriginalValue( $index )
    {
        return $this->original[ $index ];
    }

    public function jsonSerialize()
    {
        return $this->values;
    }

    public function addAction( $identifier, $action )
    {
        $this->values[ '$actions' ][ $identifier ]  =   $action;
    }

    public function removeAction( $identifier )
    {
        unset( $this->values[ '$actions' ][ $identifier ] );
    }
}