<?php

namespace App\Classes;

class JsonResponse
{
    public static function success( $data = null, $message = null )
    {
        return response()->json( [
            'success' => true,
            'data' => $data,
            'message' => $message,
        ] );
    }

    public static function error( $message = null, $data = null )
    {
        return response()->json( [
            'success' => false,
            'data' => $data,
            'message' => $message,
        ], 403 );
    }
}
