<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

class LoadLangMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if ( Auth::check() ) {
            $language   =   Auth::user()->attribute->language;
            App::setLocale( in_array( $language, array_keys( config( 'nexopos.languages' ) ) ) ? $language : 'en' );
        } else {
            App::setLocale( ns()->option->get( 'ns_store_language', 'en' ) );
        }
        
        return $next($request);
    }
}
