<?php

namespace App\Listeners;

use App\Events\ExpenseAfterUpdateEvent;
use App\Jobs\ProcessExpenseJob;

class ExpenseAfterUpdatedEventListener
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
    public function handle( ExpenseAfterUpdateEvent $event)
    {
        ProcessExpenseJob::dispatch( $event->expense );
    }
}
