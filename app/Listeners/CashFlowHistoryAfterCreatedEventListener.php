<?php

namespace App\Listeners;

use App\Events\CashFlowHistoryAfterCreatedEvent;
use App\Jobs\RefreshReportJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class CashFlowHistoryAfterCreatedEventListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle( CashFlowHistoryAfterCreatedEvent $event )
    {
        RefreshReportJob::dispatch( $event->cashFlow );
    }
}
