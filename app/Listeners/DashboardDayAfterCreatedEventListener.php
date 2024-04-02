<?php

namespace App\Listeners;

use App\Events\DashboardDayAfterCreatedEvent;
use App\Jobs\ComputeDashboardMonthReportJob;

class DashboardDayAfterCreatedEventListener
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
     * @param  object $event
     * @return void
     */
    public function handle( DashboardDayAfterCreatedEvent $event )
    {
        ComputeDashboardMonthReportJob::dispatch();
    }
}
