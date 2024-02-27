<?php

namespace App\Listeners;

use App\Events\TransactionsHistoryAfterDeletedEvent;
use App\Jobs\RefreshReportJob;

class TransactionsHistoryAfterDeletedEventListener
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
    public function handle( TransactionsHistoryAfterDeletedEvent $event )
    {
        RefreshReportJob::dispatch( $event->transaction->created_at );
    }
}
