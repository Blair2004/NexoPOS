<?php

namespace App\Exceptions;

use App\Services\Helper;
use Exception;

class CoreException extends Exception
{
    public function __construct( $message = null )
    {
        $this->message = $message ?: __('An exception has occurred.' );
    }

    public function render( $request )
    {
        $message = $this->getMessage();
        $title = __( 'An Error Occurred' );
        $back   = Helper::getValidPreviousUrl( $request );

        return response()->view( 'pages.errors.exception', compact( 'message', 'title', 'back' ), 503 );
    }
}
