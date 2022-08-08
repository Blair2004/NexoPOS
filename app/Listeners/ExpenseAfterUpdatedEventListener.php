<?php

namespace App\Listeners;

use App\Events\ExpenseAfterUpdateEvent;
use App\Jobs\ProcessExpenseJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

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
