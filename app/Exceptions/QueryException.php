<?php

namespace App\Exceptions;

use App\Services\Helper;
use Exception;
use Illuminate\Http\Request;

class QueryException extends Exception
{
    public function __construct( $message = null )
    {
        $this->message = $message ?: __( 'A Database Exception Occurred.' );
    }

    public function render( Request $request )
    {
        if ( $request->expectsJson() ) {
            return response()->json( [
                'message' => $this->getMessage(),
            ], 500 );
        }

        $message = $this->getMessage();
        $title = __( 'Query Exception' );
        $back = Helper::getValidPreviousUrl( $request );

        return response()->view( 'pages.errors.db-exception', compact( 'message', 'title', 'back' ), 500 );
    }
}
