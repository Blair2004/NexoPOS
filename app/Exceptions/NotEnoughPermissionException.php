<?php

namespace App\Exceptions;

use App\Services\Helper;
use Exception;

class NotEnoughPermissionException extends Exception
{
    public function getStatusCode()
    {
        return 403;
    }

    public function __construct( $message = null )
    {
        $this->message = $message ?: __( 'You\'re not allowed to see that page.' );
    }

    public function render( $request )
    {
        if ( ! $request->expectsJson() ) {
            return response()->view( 'pages.errors.not-enough-permissions', [
                'title' => __( 'Not Enough Permissions' ),
                'message' => $this->getMessage(),
                'back' => Helper::getValidPreviousUrl( $request ),
            ], 403 );
        }

        return response()->json( [
            'status' => 'error',
            'message' => $this->getMessage(),
        ], 403 );
    }
}
