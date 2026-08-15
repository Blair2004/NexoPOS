<?php

namespace App\Http\Middleware;

use App\Services\Helper;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class KillSessionIfNotInstalledMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response|RedirectResponse) $next
     * @return Response|RedirectResponse
     */
    public function handle( Request $request, Closure $next )
    {
        if ( ! Helper::installed() ) {
            Auth::logout();
        }

        return $next( $request );
    }
}
