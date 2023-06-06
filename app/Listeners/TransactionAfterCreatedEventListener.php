<?php

namespace App\Listeners;

use App\Events\ExpenseAfterCreateEvent;
use App\Events\TransactionAfterCreatedEvent;
use App\Jobs\ProcessExpenseJob;

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
        ProcessExpenseJob::dispatch( $event->transaction );
    }
}
