<?php
namespace App\Services;

class MapperService {
    protected $collection;
    protected $label;

    public function __construct( $collection )
    {
        $this->collection   =   $collection;
    }

    public function retreive( $label )
    {
        $this->label    =   $label;
        return $this;
    }    

    public function orReturn( callable $callback )
    {
        if ( ! isset( $this->collection[ $this->label ] ) ) {
            $this->collection[ $this->label ]   =   $callback();
        } 
        return $this->collection[ $this->label ];
    }
}