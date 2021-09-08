<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Jackiedo\DotenvEditor\Facades\DotenvEditor;

class ForceSetSessionDomainMiddleware
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
        /**
         * Set version to close setup
         */
        $domain     =   Str::replaceFirst( 'http://', '', url( '/' ) );
        $domain     =   Str::replaceFirst( 'https://', '', $domain );
        $domain     =   explode( ':', $domain )[0];

        DotenvEditor::load();
        
        if ( ! env( 'SESSION_DOMAIN', false ) ) {
            DotenvEditor::setKey( 'SESSION_DOMAIN', Str::replaceFirst( 'http://', '', explode( ':', $domain )[0] ) );
        }

        if ( ! env( 'SANCTUM_STATEFUL_DOMAINS', false ) ) {         
            DotenvEditor::setKey( 'SANCTUM_STATEFUL_DOMAINS', collect([ $domain, 'localhost', '127.0.0.1' ])->unique()->join(',') );
        }

        DotenvEditor::save();
        
        return $next($request);
    }
}
