<?php

namespace App\Http\Middleware;

use App\Events\ResponseReadyEvent;
use Closure;
use Illuminate\Http\Request;

class ClearRequestCacheMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse) $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle( Request $request, Closure $next )
    {
        $response = $next( $request );

        /**
         * In case any opeartion should occurs
         * once the response is about to bet sent.
         */
        ResponseReadyEvent::dispatch( $response );

        return $response;
    }
}
