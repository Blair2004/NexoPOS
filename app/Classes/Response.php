<?php
namespace App\Classes;

class Response 
{
    protected $output   =   [];

    public function addOutput( $view )
    {
        $this->output[]     =   $view;
    }

    public function setOutput( $view )
    {
        $this->output       =   [ $view ];
    }

    public function __toString()
    {
        return collect( $this->output )
            ->map( fn( $output ) => ( string ) $output )
            ->join( '' );
    }
}