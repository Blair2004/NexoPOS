<?php

namespace App\Http\Middleware;

use App\Exceptions\NotEnoughPermissionException;
use Closure;
use Illuminate\Http\Request;

class ProtectRoutePermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $permission )
    {
        if ( ns()->allowedTo( $permission ) ) {
            return $next($request);
        }

        throw new NotEnoughPermissionException( __( 'Your don\'t have enough permission to perform this action.' ) );
    }
}
