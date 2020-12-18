<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class MissingDependencyException extends Exception
{
    public function render( $request )
    {
        if ( ! $request->expectsJson() ) {
            return response()->view( 'pages.errors.missing-dependency', [
                'title'         =>  __( 'Missing Dependency' ),
                'message'       =>  $this->getMessage()
            ]);
        }

        return response()->json([ 
            'status'  =>  'failed',
            'message' => $this->getMessage()
        ], 401);
    }
}
