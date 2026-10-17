<?php

namespace App\Http\Middleware;

use App\Exceptions\NotEnoughPermissionException;
use Closure;
use Illuminate\Http\Request;

class NsRestrictAnyMiddleware
{
    public function handle( Request $request, Closure $next, string ...$permissions ): mixed
    {
        if ( ns()->allowedTo( $permissions ) ) {
            return $next( $request );
        }

        throw new NotEnoughPermissionException(
            sprintf( __( 'You don\'t have any of the required permissions ("%s") to perform this action.' ), implode( ', ', $permissions ) )
        );
    }
}
