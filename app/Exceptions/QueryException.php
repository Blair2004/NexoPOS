<?php

namespace App\Exceptions;

use Exception;

class QueryException extends Exception
{
    public function render( $message )
    {
        $message    =   $this->getMessage();
        $title      =   __( 'Query Exception' );
        return response()->view( 'pages.errors.db-exception', compact( 'message', 'title' ), 500 );
    }
}
