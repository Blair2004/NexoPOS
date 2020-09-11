<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class NotAllowedException extends Exception
{
    public function render( $request )
    {
        if ( ! $request->expectsJson() ) {
            return response()->view( 'pages.errors.not-allowed', [
                'title'         =>  __( 'Not Allowed Action' ),
                'message'       =>  $this->getMessage() ?: __('The action you tried to perform is not allowed.' )
            ]);
        }

        return response()->json([ 
            'status'  =>  'failed',
            'message' => $this->getMessage() ?: __('The action you tried to perform is not allowed.' )
        ], 401);
    }
}
