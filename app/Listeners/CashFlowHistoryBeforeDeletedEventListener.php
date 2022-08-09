<?php

namespace App\Listeners;

use App\Events\CashFlowHistoryBeforeDeleteEvent;
use App\Jobs\RefreshReportJob;

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
