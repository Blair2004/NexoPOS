<?php

namespace App\Exceptions;

use Exception;

class NotAllowedException extends Exception
{
    public function __construct( $message = null )
    {
        $this->message = $message ?: __('The Action You Tried To Perform Is Not Allowed.' );
    }

    public function render( $request )
    {
        if ( ! $request->expectsJson() ) {
            return response()->view( 'pages.errors.not-allowed', [
                'title' => __( 'Not Allowed Action' ),
                'message' => $this->getMessage(),
            ]);
        }

        return response()->json([
            'status' => 'failed',
            'message' => $this->getMessage() ?: __('The action you tried to perform is not allowed.' ),
        ], 401);
    }
}
