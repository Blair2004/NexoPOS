<?php

namespace App\Exceptions;

use Exception;

class MethodNotAllowedHttpException extends Exception
{
    public function render( $message )
    {
        $message    =   $this->getMessage();
        $title      =   __( 'Method Not Allowed' );
        return response()->view( 'pages.errors.http-exception', compact( 'message', 'title' ), 500 );
    }
}
