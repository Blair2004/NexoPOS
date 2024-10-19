<?php

namespace App\Listeners;

use App\Events\TransactionsHistoryBeforeDeleteEvent;
use App\Jobs\DeleteAccountingReflectionJob;

class TransactionsHistoryBeforeDeletedEventListener
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
    public function handle( TransactionsHistoryBeforeDeleteEvent $event )
    {
        DeleteAccountingReflectionJob::dispatch( $event->transactionHistory );
    }
}
