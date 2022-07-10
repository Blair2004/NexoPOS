<?php

namespace App\Exceptions;

use Exception;

class CoreException extends Exception
{
    public function __construct( $message = null )
    {
        $this->message = $message ?: __('An exception has occurred.' );
    }

    public function render()
    {
        $message = $this->getMessage();
        $title = __( 'An Error Occurred' );

        return response()->view( 'pages.errors.exception', compact( 'message', 'title' ), 503 );
    }
}
