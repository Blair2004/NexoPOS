<?php

namespace App\Http\Middleware;

use App\Models\Migration;
use App\Services\Helpers\App;
use App\Services\ModulesService;
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
            return redirect( ns()->route( 'ns.database-update' ) );
        }

        if ( App::installed() ) {
            $module     =   app()->make( ModulesService::class );
            $modules    =   collect( $module->getEnabled() );
            $total      =   $modules->filter( fn( $module ) => count( $module[ 'migrations' ] ) > 0 );
            
            if ( $total->count() > 0 ) {
                return redirect( ns()->route( 'ns.database-update' ) );
            }
        }

        return $next($request);
    }
}
