<?php

namespace App\Http\Middleware;

use Closure;
use App\Classes\Hook;
use Illuminate\Http\Request;

class HandleCommonRoutesMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if( $resultRequest = Hook::filter( 'ns-common-routes', null, $request, $next ) === null ) {
            return $next( $request );
        }

        return $resultRequest;
    }
}
