<?php

namespace App\Jobs;

use App\Enums\NotificationsEnum;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckTaskSchedulingConfigurationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if ( env( 'QUEUE_CONNECTION' ) !== 'sync' ) {
            /**
             * @var NotificationService
             */
            $notification = app()->make( NotificationService::class );
            $notification->deleteHavingIdentifier( NotificationsEnum::NSWORKERDISABLED );

            ns()->option->set( 'ns_jobs_last_activity', ns()->date->toDateTimeString() );
        }
    }
}
