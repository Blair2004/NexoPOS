<?php

namespace App\Exceptions;

use App\Services\Helper;
use Exception;
use Illuminate\Http\Request;

class CoreException extends Exception
{
    public function __construct( $message = null )
    {
        $this->message = $message ?: __( 'A critical error has occured.' );
    }
}
