<?php

namespace App\Listeners;

use App\Events\DashboardDayAfterUpdatedEvent;
use App\Jobs\ComputeDashboardMonthReportJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class DashboardDayAfterUpdatedEventListener
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
     * @param  object  $event
     * @return void
     */
    public function handle( DashboardDayAfterUpdatedEvent $event)
    {
        ComputeDashboardMonthReportJob::dispatch()
            ->delay( now()->addSeconds(10) );
    }
}
