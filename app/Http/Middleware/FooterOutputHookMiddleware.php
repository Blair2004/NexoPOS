<?php

namespace App\Http\Middleware;

use App\Classes\Hook;
use App\Classes\Output;
use Closure;
use Illuminate\Http\Request;

class FooterOutputHookMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        Hook::addAction( 'ns-dashboard-footer', function ( Output $output ) {
            $exploded = explode( '.', request()->route()->getName() );

            /**
             * a route might not have a name
             * if that happen, we'll ignore that.
             */
            if ( ! empty( $exploded ) ) {
                $final = implode( '-', $exploded ) . '-footer';
                Hook::action( $final, $output );
            }
        }, 15 );

        return $next($request);
    }
}
