<?php

namespace App\Http\Middleware;

use App\Events\AfterAppHealthCheckedEvent;
use App\Services\ModulesService;
use Closure;
use Illuminate\Http\Request;

class CheckApplicationHealthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle( Request $request, Closure $next )
    {
        /**
         * Will check if either "redis" or "supervisor" is configured
         * and will emit a notification if it's not the case.
         */
        ns()->checkTaskSchedulingConfiguration();

        /**
         * We'll only perform this is the QUEUE_CONNECTION
         * has a supported value. Otherwise it's performed asynchronously see app/Console/Kernel.php
         */
        if ( in_array( env( 'QUEUE_CONNECTION' ), [ 'sync' ] ) ) {
            /**
             * Will check if Cron Jobs are
             * correctly set for NexoPOS
             */
            ns()->checkCronConfiguration();

            /**
             * Will check wether symbolic link
             * is created to the storage
             */
            ns()->checkSymbolicLinks();
        }

        /**
         * we'll check here is a module has a missing
         * dependency to disable it
         *
         * @var ModulesService
         */
        $modules = app()->make( ModulesService::class );
        $modules->dependenciesCheck();

        AfterAppHealthCheckedEvent::dispatch( $next, $request );

        return $next( $request );
    }
}
