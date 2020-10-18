<?php
namespace App\Classes;

use Illuminate\Support\Facades\View;

class Response 
{
    protected $output   =   [];

    public function addOutput( $view )
    {
        $this->output[]     =   $view;
    }

    public function addView( $view )
    {
        $this->output[]     =   View::make( $view );
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