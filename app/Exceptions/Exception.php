<?php

namespace App\Exceptions;

use Exception as CoreException;

class Exception extends CoreException
{
    public function render( $message )
    {
        $message    =   $this->getMessage();
        $title      =   __( 'An Error Occured' );
        return response()->view( 'pages.errors.exception', compact( 'message', 'title' ), 503 );
    }
}
