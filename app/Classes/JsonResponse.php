<?php

namespace App\Classes;

class JsonResponse
{
    /**
     * Returns a success response.
     * 
     * @param $data
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    public static function success( string $message, array $data = [] )
    {
        return response()->json( [
            'status' => 'success',
            'data' => $data,
            'message' => $message,
        ] );
    }

    /**
     * Returns an error response.
     * 
     * @param string $message
     * @param $data
     * @return \Illuminate\Http\JsonResponse
     */
    public static function error( string $message, array $data = [] )
    {
        return response()->json( [
            'status' => 'error',
            'data' => $data,
            'message' => $message,
        ], 403 );
    }

    /**
     * Returns an infos response.
     * 
     * @param string $message
     * @param $data
     * @return \Illuminate\Http\JsonResponse
     */
    public static function info( string $message, array $data = [] )
    {
        return response()->json( [
            'status' => 'info',
            'data' => $data,
            'message' => $message,
        ] );
    }
}
