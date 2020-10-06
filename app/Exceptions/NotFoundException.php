<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class NotFoundException extends Exception
{
    public function render( $request )
    {
        $message    =   __('The resource of the page you tried to access is not available or might have been deleted.' );
        
        if ( ! $request->expectsJson() ) {
            return response()->view( 'pages.errors.not-allowed', [
                'title'         =>  __( 'Not Found Exception' ),
                'message'       =>  $this->getMessage() ?: $message
            ]);
        }

        return response()->json([ 
            'status'  =>  'failed',
            'message' => $this->getMessage() ?: $message
        ], 401);
    }
}
