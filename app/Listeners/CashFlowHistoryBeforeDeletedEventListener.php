<?php

namespace App\Listeners;

use App\Events\CashFlowHistoryBeforeDeleteEvent;

class CashFlowHistoryBeforeDeletedEventListener
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
     *
     * @param  \App\Events\CashFlowHistoryBeforeDeletedEvent  $event
     * @return void
     */
    public function handle(CashFlowHistoryBeforeDeleteEvent $event)
    {
        // ...
    }
}
