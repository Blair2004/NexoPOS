<?php

namespace App\Exceptions;

use App\Services\Helper;
use Exception;
use Illuminate\Http\Request;

class CoreException extends Exception
{
    public function render( Request $request, $exception )
    {
        $title      = __( 'Oops, We\'re Sorry!!!' );
        $back       = Helper::getValidPreviousUrl( $request );
        $message    = $exception->getMessage() ?: sprintf( __( 'Class: %s' ), get_class( $exception ) );

        if ( $request->expectsJson() ) {
            return response()->json([
                'status'    =>  'failed',
                'message'   =>  $message,
                'data'      =>  [
                    'class' =>  $exception::CLASS,
                    'previous'  =>  $back
                ]
            ], method_exists( $exception, 'getStatusCode' ) ? $exception->getStatusCode() : 501 );
        }        

        return response()->view( 'pages.errors.exception', compact( 'message', 'title', 'back' ), 503 );
    }
}
