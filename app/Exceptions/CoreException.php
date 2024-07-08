<?php

namespace App\Exceptions;

use Exception;

class CoreException extends Exception
{
    public function __construct( $message = null )
    {
        $this->message = $message ?: __( 'A critical error has occured.' );
    }
}
