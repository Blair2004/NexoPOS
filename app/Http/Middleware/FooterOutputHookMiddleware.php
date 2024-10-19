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
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse) $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle( Request $request, Closure $next )
    {
        /**
         * This allows creating custom header and footer hook action
         * using the route name. For example a route having as name "ns.dashboard.home"
         * Will now have 2 hooks "ns-dashboard-home-header" and "ns-dashboard-home-footer"
         */
        collect( [ 'header', 'footer' ] )->each( function ( $arg ) {
            $hookName = 'ns-dashboard-' . $arg;

            Hook::addAction( $hookName, function ( Output $output ) use ( $arg ) {
                $exploded = explode( '.', request()->route()->getName() );

                /**
                 * a route might not have a name
                 * if that happen, we'll ignore that.
                 */
                if ( ! empty( $exploded ) ) {
                    $final = implode( '-', $exploded ) . '-' . $arg;
                    Hook::action( $final, $output );
                }
            }, 15 );
        } );

        return $next( $request );
    }
}
