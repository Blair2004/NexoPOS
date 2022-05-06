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
}