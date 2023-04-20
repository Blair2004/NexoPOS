<?php

namespace App\Exceptions;

use App\Services\Helper;
use Exception;

class QueryException extends Exception
{
    public function __construct( $message = null )
    {
        $this->message = $message ?: __('A Database Exception Occurred.' );
    }

    public function render( $request )
    {
        $message = $this->getMessage();
        $title = __( 'Query Exception' );
        $back   = Helper::getValidPreviousUrl( $request );

        return response()->view( 'pages.errors.db-exception', compact( 'message', 'title', 'back' ), 500 );
    }
}
