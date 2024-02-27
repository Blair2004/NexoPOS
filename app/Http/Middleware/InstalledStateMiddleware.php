<?php

namespace App\Http\Middleware;

use App\Events\InstalledStateBeforeCheckedEvent;
use App\Services\Helper;
use Closure;

class InstalledStateMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @return mixed
     */
    public function handle( $request, Closure $next )
    {
        InstalledStateBeforeCheckedEvent::dispatch( $next, $request );

        if ( Helper::installed() ) {
            return $next( $request );
        }

        return redirect()->route( 'ns.do-setup' );
    }
}
