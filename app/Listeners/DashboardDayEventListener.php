<?php

namespace App\Listeners;

use App\Events\DashboardDayAfterComputedEvent;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class DashboardDayEventListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(
        public ReportService $reportService
    )
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\DashboardDayAfterComputedEvent  $event
     * @return void
     */
    public function handle(DashboardDayAfterComputedEvent $event)
    {
        $createdAt    =   Carbon::parse( $event->dashboardDay->created_at );
        
        $this->reportService->computeDashboardMonth( $createdAt );
    }
}
