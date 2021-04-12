<?php

namespace App\Http\Middleware;

use App\Exceptions\NotAllowedException;
use App\Exceptions\NotEnoughPermissionException;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProtectRouteRoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $role )
    {
        if ( Auth::check() && ns()->hasRole( $role ) ) {
            return $next($request);
        }

        throw new NotAllowedException( __( 'You don\'t have the necessary role to see this page.' ) );
    }
}
