<?php

namespace App\Listeners;

use App\Events\TransactionsHistoryAfterDeletedEvent;

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
        // ...
    }
}
