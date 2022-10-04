<?php

namespace App\Exceptions;

use Exception;

class CoreVersionMismatchException extends Exception
{
    public $title = '';

    public function __construct( $message = null )
    {
        $this->message = $message ?: __('There\'s is mismatch with the core version.' );
    }

    public function render()
    {
        $message = $this->getMessage();
        $title = $this->title ?: __( 'Incompatibility Exception' );

        return response()->view( 'pages.errors.core-exception', compact( 'message', 'title' ), 500 );
    }
}
