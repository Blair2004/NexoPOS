<?php

namespace App\Listeners;

use App\Events\CashFlowHistoryAfterDeletedEvent;
use App\Jobs\RefreshReportJob;

class CashFlowHistoryAfterDeletedEventListener
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
     *
     * @return void
     */
    public function handle(CashFlowHistoryAfterDeletedEvent $event)
    {
        RefreshReportJob::dispatch( $event->cashFlow->created_at );
    }
}
