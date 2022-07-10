<?php

namespace App\Exceptions;

use Exception;

class ModuleVersionMismatchException extends Exception
{
    public function __construct( $message = null )
    {
        $this->message = $message ?: __('A database issue has occurred.' );
    }

    public function render()
    {
        $message = $this->getMessage();
        $title = $this->title ?? __( 'Module Version Mismatch' );

        return response()->view( 'pages.errors.module-exception', compact( 'message', 'title' ), 500 );
    }
}
