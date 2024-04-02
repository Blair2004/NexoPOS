<?php

namespace App\Exceptions;

use App\Services\Helper;
use Exception;

class CoreVersionMismatchException extends Exception
{
    public $title = '';

    public function __construct( $message = null )
    {
        $this->message = $message ?: __( 'There\'s is mismatch with the core version.' );
    }

    public function render( $request )
    {
        $message = $this->getMessage();
        $title = $this->title ?: __( 'Incompatibility Exception' );
        $back = Helper::getValidPreviousUrl( $request );

        return response()->view( 'pages.errors.core-exception', compact( 'message', 'title', 'back' ), 500 );
    }
}
