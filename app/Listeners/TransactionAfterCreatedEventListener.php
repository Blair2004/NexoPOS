<?php

namespace App\Listeners;

use App\Events\TransactionAfterCreatedEvent;
use App\Jobs\ProcessTransactionJob;

class TransactionAfterCreatedEventListener
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
    public function handle( TransactionAfterCreatedEvent $event )
    {
        ProcessTransactionJob::dispatch( $event->transaction );
    }
}
