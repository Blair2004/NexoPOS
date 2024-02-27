<?php

namespace App\Exceptions;

use App\Services\Helper;
use Exception;

class MethodNotAllowedHttpException extends Exception
{
    public function __construct( $message = null )
    {
        $this->message = $message ?: __( 'The request method is not allowed.' );
    }

    public function render( $request )
    {
        $message = $this->getMessage();
        $title = __( 'Method Not Allowed' );
        $back = Helper::getValidPreviousUrl( $request );

        return response()->view( 'pages.errors.http-exception', compact( 'message', 'title', 'back' ), 500 );
    }
}
