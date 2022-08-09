<?php

namespace App\Listeners;

use App\Events\OrderBeforeDeleteEvent;
use App\Jobs\ComputeDayReportJob;
use App\Jobs\UncountDeletedOrderForCashierJob;
use App\Jobs\UncountDeletedOrderForCustomerJob;

class OrderBeforeDeleteEventListener
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
     * @param  \App\Events\OrderBeforeDeleteEvent  $event
     * @return void
     */
    public function handle(OrderBeforeDeleteEvent $event)
    {
        // ...
    }
}
