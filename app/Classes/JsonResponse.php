<?php

namespace App\Classes;

class JsonResponse
{
    public static function success( $data = null, $message = null )
    {
        return response()->json( [
            'status' => 'success',
            'data' => $data,
            'message' => $message,
        ] );
    }

    public static function error( $message = null, $data = null )
    {
        return response()->json( [
            'status' => 'error',
            'data' => $data,
            'message' => $message,
        ], 403 );
    }
}
