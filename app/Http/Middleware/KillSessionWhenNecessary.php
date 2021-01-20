<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class KillSessionWhenNecessary
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
        if ( ! ns()->installed() && ! empty( request()->cookie( 'nexopos_session' ) ) ) {
            Cookie::queue( Cookie::make( 'nexopos_session', null, 0 ) );
            session()->flush();
        }

        return $next($request);
    }
}
