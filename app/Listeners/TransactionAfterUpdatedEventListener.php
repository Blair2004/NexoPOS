<?php

namespace App\Listeners;

use App\Events\TransactionAfterUpdatedEvent;
use App\Jobs\ProcessTransactionJob;

class TransactionAfterUpdatedEventListener
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
    public function handle( TransactionAfterUpdatedEvent $event )
    {
        ProcessTransactionJob::dispatch( $event->transaction );
    }
}
