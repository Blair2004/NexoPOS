<?php
namespace App\Services\Helpers;
use Illuminate\Http\Request;

trait Routes
{
    /**
     * RefererRouteIs
     * test current route according to referer route provided on _route POST field.
     * @param string
     * @return boolean
     */
    public static function RefererRouteIs( $route )
    {
        $request    =   app()->make( Request::class );
        return $request->input( '_route' ) === $route;
    }
}