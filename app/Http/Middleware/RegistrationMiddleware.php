<?php

namespace App\Http\Middleware;

use App\Exceptions\NotAllowedException;
use Closure;
use Illuminate\Http\Request;

class RegistrationMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse) $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle( Request $request, Closure $next )
    {
        if ( ns()->option->get( 'ns_registration_enabled', 'no' ) === 'yes' ) {
            return $next( $request );
        }

        throw new NotAllowedException( __( 'The registration has been explicitly disabled.' ) );
    }
}
