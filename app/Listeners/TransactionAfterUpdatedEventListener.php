<?php

namespace App\Listeners;

use App\Events\ExpenseAfterUpdateEvent;
use App\Events\TransactionAfterUpdatedEvent;
use App\Jobs\ProcessExpenseJob;

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
    public function handle( TransactionAfterUpdatedEvent $event)
    {
        ProcessExpenseJob::dispatch( $event->transaction );
    }
}
