<?php

namespace App\Exceptions;

use Exception;

class MethodNotAllowedHttpException extends Exception
{
    public function __construct( $message = null ) 
    {
        $this->message  =   $message ?: __('The request method is no allowed.' );
    }

    public function render( $message )
    {
        $message    =   $this->getMessage();
        $title      =   __( 'Method Not Allowed' );
        return response()->view( 'pages.errors.http-exception', compact( 'message', 'title' ), 500 );
    }
}
