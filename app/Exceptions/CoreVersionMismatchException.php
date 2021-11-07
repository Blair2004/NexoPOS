<?php

namespace App\Exceptions;

use Exception;

class CoreVersionMismatchException extends Exception
{
    public function render( $message )
    {
        $message    =   $this->getMessage();
        $title      =   $this->title ?: __( 'Incompatibility Exception' );
        return response()->view( 'pages.errors.core-exception', compact( 'message', 'title' ), 500 );
    }
}
