<?php

namespace App\Listeners;

use App\Events\ExpenseAfterCreateEvent;
use App\Jobs\ProcessExpenseJob;

class ExpenseAfterCreatedEventListener
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
    public function handle( ExpenseAfterCreateEvent $event )
    {
        ProcessExpenseJob::dispatch( $event->expense );
    }
}
