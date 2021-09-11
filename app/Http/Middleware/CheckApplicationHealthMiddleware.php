<?php

namespace App\Http\Middleware;

use App\Enums\NotificationsEnum;
use App\Events\AfterAppHealthCheckedEvent;
use App\Jobs\TaskSchedulingPingJob;
use App\Models\Role;
use App\Services\DateService;
use App\Services\ModulesService;
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
        if ( ns()->option->get( 'ns_cron_ping', false ) === false ) {
            /**
             * @var NotificationsEnum;
             */
            $this->emitMisconfigurationNotification();

            /**
             * force dispatching the job
             * to force check the tasks status.
             */
            TaskSchedulingPingJob::dispatch()->delay( now() );

        } else {
            /**
             * @var DateService
             */
            $date               =   app()->make( DateService::class );
            $lastUpdate         =   Carbon::parse( ns()->option->get( 'ns_cron_ping' ) );

            if ( $lastUpdate->diffInMinutes( $date->now() ) > 60 ) {
                $this->emitMisconfigurationNotification();

                /**
                 * force dispatching the job
                 * to force check the tasks status.
                 */
                TaskSchedulingPingJob::dispatch()->delay( now() );
            } 
        }

        /**
         * we'll check here is a module has a missing 
         * dependency to disable it
         * @var ModulesService
         */
        $modules        =   app()->make( ModulesService::class );
        $modules->dependenciesCheck();

        event( new AfterAppHealthCheckedEvent );

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
            'description'   =>      __( "NexoPOS is unable to run tasks correctly. This happens if Queues or Tasks Scheduling aren't configured correctly." ),
        ])->dispatchForGroup( Role::namespace( 'admin' ) );
    }
}
