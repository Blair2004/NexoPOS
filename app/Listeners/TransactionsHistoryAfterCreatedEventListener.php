<?php

namespace App\Listeners;

use App\Events\TransactionsHistoryAfterCreatedEvent;
use App\Jobs\RefreshReportJob;

class TransactionsHistoryAfterCreatedEventListener
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
    public function handle( TransactionsHistoryAfterCreatedEvent $event )
    {
        RefreshReportJob::dispatch( $event->transactionHistory->created_at );
    }
}
