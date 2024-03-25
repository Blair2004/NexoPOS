<?php

namespace App\Exceptions;

use App\Services\Helper;
use Illuminate\Http\Exceptions\PostTooLargeException as ExceptionsPostTooLargeException;

class PostTooLargeException extends ExceptionsPostTooLargeException
{
    public function render( $request )
    {
        if ( ! $request->expectsJson() ) {
            return response()->view( 'pages.errors.not-allowed', [
                'title' => __( 'Post Too Large' ),
                'message' => __( 'The submitted request is more large than expected. Consider increasing your "post_max_size" on your PHP.ini' ),
                'back' => Helper::getValidPreviousUrl( $request ),
            ] );
        }

        return response()->json( [
            'status' => 'error',
            'message' => __( 'The submitted request is more large than expected. Consider increasing your "post_max_size" on your PHP.ini' ),
        ], 401 );
    }
}
