<?php

namespace App\Http\Middleware;

use App\Exceptions\NotEnoughPermissionException;
use App\Traits\NsMiddlewareArgument;
use Closure;
use Illuminate\Http\Request;

class NsRestrictMiddleware
{
    use NsMiddlewareArgument;

    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle( Request $request, Closure $next, $permission )
    {
        if ( ns()->allowedTo( $permission ) ) {
            return $next( $request );
        }

        $message = sprintf(
            __( 'Your don\'t have enough permission ("%s") to perform this action.' ),
            $permission
        );

        throw new NotEnoughPermissionException( $message );
    }
}
