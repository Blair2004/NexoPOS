<?php

namespace App\Http\Middleware;

use App\Enums\NotificationsEnum;
use App\Models\Role;
use App\Services\NotificationService;
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
        if ( env( 'NS_CRON_STATUS', false ) === false ) {
            /**
             * @var NotificationsEnum;
             */
            
            $notification       =   app()->make( NotificationService::class );
            $notification->create([
                'title'         =>      __( 'Workers Misconfiguration' ),
                'identifier'    =>      NotificationsEnum::NSCRONDISABLED,
                'source'        =>      'system',
                'url'           =>      'https://laravel.com/docs/8.x/scheduling#starting-the-scheduler',
                'description'   =>      __( 'NexoPOS is unable to run jobs(workers) correctly. This happens if Queues or and Tasks Scheduling aren\'t configured correctly.' )
            ])->dispatchForGroup( Role::namespace( 'admin' ) );
        }

        return $next($request);
    }
}
