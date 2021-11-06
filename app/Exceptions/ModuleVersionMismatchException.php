<?php

namespace App\Exceptions;

use Exception;

class ModuleVersionMismatchException extends Exception
{
    public function render( $message )
    {
        $message    =   $this->getMessage();
        $title      =   $this->title ?? __( 'Module Version Mismatch' );
        return response()->view( 'pages.errors.module-exception', compact( 'message', 'title' ), 500 );
    }
}
