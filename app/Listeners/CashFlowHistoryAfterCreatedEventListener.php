<?php

namespace App\Listeners;

use App\Events\CashFlowHistoryAfterCreatedEvent;
use App\Jobs\RefreshReportJob;

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
        RefreshReportJob::dispatch( $event->transactionHistory->created_at );
    }
}
