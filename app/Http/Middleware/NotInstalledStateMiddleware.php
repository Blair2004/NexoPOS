<?php

namespace App\Http\Middleware;

use App\Exceptions\NotAllowedException;
use App\Services\Helper;
use Closure;
use Illuminate\Support\Facades\App;

class NotInstalledStateMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @return mixed
     */
    public function handle( $request, Closure $next )
    {
        /**
         * we'll try to detect the language
         * from the query string.
         */
        if ( ! empty( $request->query( 'lang' ) ) ) {
            $validLanguage = in_array( $request->query( 'lang' ), array_keys( config( 'nexopos.languages' ) ) ) ? $request->query( 'lang' ) : 'en';
            App::setLocale( $validLanguage );
        }

        if ( ! Helper::installed() ) {
            return $next( $request );
        }

        throw new NotAllowedException( __( 'You\'re not allowed to see this page.' ) );
    }
}
