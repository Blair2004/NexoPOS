<?php

namespace App\Http\Middleware;

use App\Classes\Hook;
use Closure;
use Illuminate\Http\Request;

class HandleCommonRoutesMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle( Request $request, Closure $next )
    {
        $resultRequest = Hook::filter( 'ns-common-routes', false, $request, $next );

        if ( $resultRequest === false ) {
            return $next( $request );
        }

        return $resultRequest;
    }
}
