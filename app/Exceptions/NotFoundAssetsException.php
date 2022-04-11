<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Request;

class NotFoundAssetsException extends Exception
{
    public function render( Request $message )
    {
        $message    =   $this->getMessage();
        $title      =   __( 'Not Found Assets' );
        return response()->view( 'pages.errors.assets-exception', compact( 'message', 'title' ), 500 );
    }
}
