<?php

namespace App\Jobs;

use App\Enums\NotificationsEnum;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Jackiedo\DotenvEditor\Facades\DotenvEditor;

class ApplicationHealthJob implements ShouldQueue
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
        if ( ! DotenvEditor::keyExists( 'NS_CRON_STATUS' ) ) {
            /**
             * @var NotificationService
             */
            $notification       =   app()->make( NotificationService::class );
            $notification->deleteHavingIdentifier( NotificationsEnum::NSCRONDISABLED );
            DotenvEditor::setKey( 'NS_CRON_STATUS', true );
            DotenvEditor::save();
        }
    }
}
