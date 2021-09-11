<?php

namespace App\Listeners;

use App\Events\AfterAppHealthCheckedEvent;
use App\Events\DashboardDayAfterCreatedEvent;
use App\Events\DashboardDayAfterUpdatedEvent;
use App\Jobs\ComputeDashboardMonthReportJob;
use App\Jobs\InitializeDailyReportJob;
use App\Models\DashboardDay;
use App\Services\ReportService;

class CoreEventSubscriber
{
    public $reportService;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(
        ReportService $reportService
    )
    {
        $this->reportService    =   $reportService;
    }

    public function subscribe( $event )
    {
        return [
            AfterAppHealthCheckedEvent::class       => 'initializeJobReport',
            DashboardDayAfterCreatedEvent::class    => 'dispatchMonthReportUpdate',
            DashboardDayAfterUpdatedEvent::class    => 'dispatchMonthReportUpdate',
        ];
    }

    public function dispatchMonthReportUpdate( $event )
    {
        ComputeDashboardMonthReportJob::dispatch()
            ->delay( now()->addSeconds(10) );
    }

    public function initializeJobReport()
    {
        if ( ! DashboardDay::forToday() instanceof DashboardDay ) {
            InitializeDailyReportJob::dispatch()->delay( now() );
        }
    }
}
