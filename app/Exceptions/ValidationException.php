<?php

namespace App\Exceptions;

use Illuminate\Validation\ValidationException as MainValidationException;

class ValidationException extends MainValidationException
{
    public function render( $request )
    {
        $message    =   __('The resource of the page you tried to access is not available or might have been deleted.' );
        
        if ( ! $request->expectsJson() ) {
            return response()->view( 'pages.errors.not-allowed', [
                'title'         =>  __( 'Unable to proceed the form is not valid' ),
                'message'       =>  $this->getMessage() ?: $message
            ]);
        }

        return response()->json([ 
            'status'  =>    'failed',
            'message' =>    $this->getMessage() ?: $message,
            'data'    =>    [
                'errors'    =>  $this->errors()
            ]
        ], 401);
    }
}
