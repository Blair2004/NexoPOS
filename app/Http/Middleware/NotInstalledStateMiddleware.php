<?php

namespace App\Http\Middleware;

use Closure;
use Exception;

class NotInstalledStateMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ( ! ns()->installed() ) {
            return $next($request);
        }
        
        throw new Exception( __( 'You\'re not allowed to see this apge.' ) );
    }
}
