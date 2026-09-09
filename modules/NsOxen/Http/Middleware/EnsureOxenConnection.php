<?php

namespace Modules\NsOxen\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOxenConnection
{
    public function handle( Request $request, Closure $next ): Response
    {
        $user = $request->user();
        if ( ! $user || ! $user->allowedTo( ['ns.oxen.use'] ) || ( $user->currentAccessToken() && ! $user->tokenCan( 'oxen:connect' ) ) ) {
            return response()->json( ['code' => $user ? 'FORBIDDEN' : 'UNAUTHENTICATED', 'message' => __m( 'Oxen connection denied.', 'NsOxen' )], $user ? 403 : 401 );
        }

return $next( $request );
    }
}
