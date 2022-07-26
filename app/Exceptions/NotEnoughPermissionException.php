<?php

namespace App\Exceptions;

use Exception;

class NotEnoughPermissionException extends Exception
{
    public function __construct( $message = null )
    {
        $this->message = $message ?: __('A Database Exception Occurred.' );
    }

    public function render( $request )
    {
        if ( ! $request->expectsJson() ) {
            return response()->view( 'pages.errors.not-enough-permissions', [
                'title' => __( 'Not Enough Permissions' ),
                'message' => $this->getMessage() ?: __('You\'re not allowed to see that page.' ),
            ]);
        }

        return response()->json([
            'status' => 'failed',
            'message' => $this->getMessage() ?: __('You\'re not allowed to see that page.' ),
        ], 401);
    }
}
