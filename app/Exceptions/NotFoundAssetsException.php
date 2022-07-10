<?php

namespace App\Exceptions;

use Exception;

class NotFoundAssetsException extends Exception
{
    public function __construct( $message = null )
    {
        $this->message = $message ?: __('Unable to locate the assets.' );
    }

    public function render()
    {
        $message = $this->getMessage();
        $title = __( 'Not Found Assets' );

        return response()->view( 'pages.errors.assets-exception', compact( 'message', 'title' ), 500 );
    }
}
