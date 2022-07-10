<?php

namespace App\Exceptions;

use Exception;

class QueryException extends Exception
{
    public function __construct( $message = null )
    {
        $this->message = $message ?: __('A Database Exception Occurred.' );
    }

    public function render()
    {
        $message = $this->getMessage();
        $title = __( 'Query Exception' );

        return response()->view( 'pages.errors.db-exception', compact( 'message', 'title' ), 500 );
    }
}
