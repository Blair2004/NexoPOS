<?php

namespace App\Http\Middleware;

use App\Models\Migration;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CheckMigrationStatus
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
        if ( ns()->update->getMigrations()->count() > 0 ) {
            session([ 'after_update' => url()->current() ]);
            return redirect( route( 'ns.database-update' ) );
        }

        return $next($request);
    }
}
