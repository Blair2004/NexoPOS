<?php

namespace App\Http\Middleware;

use App\Exceptions\NotAllowedException;
use Closure;

class NotInstalledStateMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ( ! ns()->installed() ) {
            return $next($request);
        }

        throw new NotAllowedException( __( 'You\'re not allowed to see this page.' ) );
    }
}
