<?php

namespace App\Http\Middleware;

use App\Traits\NsMiddlewareArgument;
use Closure;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
    use NsMiddlewareArgument;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  string|null              $guard
     * @return mixed
     */
    public function handle( $request, Closure $next, $guard = null )
    {
        if ( Auth::guard( $guard )->check() ) {
            return redirect( '/' );
        }

        return $next( $request );
    }
}
