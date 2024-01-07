<?php

namespace App\Jobs;

use App\Services\NotificationService;
use App\Services\Options;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class TestWorkerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $notification_id;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct( $notification_id )
    {
        $this->notification_id = $notification_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle( Options $options, NotificationService $notification )
    {
        if ( $options->get( 'ns_workers_enabled' ) === 'await_confirm' ) {
            $options->set( 'ns_workers_enabled', 'yes' );
            $notification->deleteHavingIdentifier( $this->notification_id );
        }
    }
}
