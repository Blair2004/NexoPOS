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
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        /**
         * Will check if either "redis" or "supervisor" is configured
         * and will emit a notification if it's not the case.
         */
        ns()->checkTaskSchedulingConfiguration();

        /**
         * Will check if Cron Jobs are correctly set for
         * NexoPOS 4x
         */
        ns()->checkCronConfiguration();

        /**
         * we'll check here is a module has a missing
         * dependency to disable it
         *
         * @var ModulesService
         */
        $modules = app()->make( ModulesService::class );
        $modules->dependenciesCheck();

        AfterAppHealthCheckedEvent::dispatch();

        return $next($request);
    }
}
