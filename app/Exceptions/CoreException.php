<?php

namespace App\Exceptions;

use Exception;

class CoreException extends Exception
{
    public function __construct( $message = null ) 
    {
        $this->message  =   $message ?: __('An exception has occured.' );
    }

    public function render()
    {
        $message    =   $this->getMessage();
        $title      =   __( 'An Error Occured' );
        return response()->view( 'pages.errors.exception', compact( 'message', 'title' ), 503 );
    }
}
