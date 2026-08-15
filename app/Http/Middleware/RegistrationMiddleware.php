<?php

namespace App\Http\Middleware;

use App\Exceptions\NotAllowedException;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class RegistrationMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response|RedirectResponse) $next
     * @return Response|RedirectResponse
     */
    public function handle( Request $request, Closure $next )
    {
        if ( ns()->option->get( 'ns_registration_enabled', 'no' ) === 'yes' ) {
            return $next( $request );
        }

        throw new NotAllowedException( __( 'The registration has been explicitly disabled.' ) );
    }
}
