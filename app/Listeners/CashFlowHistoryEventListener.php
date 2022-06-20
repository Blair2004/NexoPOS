<?php

namespace App\Listeners;

use App\Events\CashFlowHistoryAfterCreatedEvent;
use App\Events\CashFlowHistoryAfterDeletedEvent;
use App\Jobs\RefreshReportJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class CashFlowHistoryEventListener
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

    public function subscribe( $event )
    {
        match( get_class( $event ) ) {
            CashFlowHistoryAfterCreatedEvent::class     =>  RefreshReportJob::dispatch( $event ),
            CashFlowHistoryAfterDeletedEvent::class     =>  RefreshReportJob::dispatch( $event ),
            CashFlowHistoryAfterUpdatedEvent::class     =>  RefreshReportJob::dispatch( $event )
        };
    }
}
