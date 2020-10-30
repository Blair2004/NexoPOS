<?php

namespace App\Http\Middleware;

use App\Enums\NotificationsEnum;
use App\Models\Role;
use App\Services\DateService;
use App\Services\NotificationService;
use Carbon\Carbon;
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
        if ( env( 'NS_CRON_PING', false ) === false ) {
            /**
             * @var NotificationsEnum;
             */
            $this->emitMisconfigurationNotification();

        } else {
            /**
             * @var DateService
             */
            $date               =   app()->make( DateService::class );
            $lastUpdate         =   Carbon::parse( env( 'NS_CRON_PING' ) );

            if ( $lastUpdate->diffInMinutes( $date->now() ) > 60 ) {
                $this->emitMisconfigurationNotification();
            }
        }

        return $next($request);
    }

    /**
     * Will emit notification if it has to
     * @return void
     */
    public function emitMisconfigurationNotification()
    {
        $notification       =   app()->make( NotificationService::class );
        $notification->create([
            'title'         =>      __( 'Workers Misconfiguration' ),
            'identifier'    =>      NotificationsEnum::NSCRONDISABLED,
            'source'        =>      'system',
            'url'           =>      'https://laravel.com/docs/8.x/scheduling#starting-the-scheduler',
            'description'   =>      __( 'NexoPOS is unable to run tasks correctly. This happens if Queues or Tasks Scheduling aren\'t configured correctly.' )
        ])->dispatchForGroup( Role::namespace( 'admin' ) );
    }
}
