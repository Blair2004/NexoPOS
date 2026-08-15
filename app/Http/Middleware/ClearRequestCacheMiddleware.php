<?php

namespace App\Http\Middleware;

use App\Events\ResponseReadyEvent;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ClearRequestCacheMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response|RedirectResponse) $next
     * @return Response|RedirectResponse
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
