<?php

namespace App\Listeners;

use App\Events\AfterAppHealthCheckedEvent;
use App\Events\DashboardDayAfterCreatedEvent;
use App\Events\DashboardDayAfterUpdatedEvent;
use App\Jobs\ComputeDashboardMonthReportJob;
use App\Jobs\InitializeDailyReportJob;
use App\Models\DashboardDay;
use App\Services\ReportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

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
        $event->listen(
            AfterAppHealthCheckedEvent::class,
            [ CoreEventSubscriber::class, 'initializeJobReport' ]
        );

        $event->listen(
            DashboardDayAfterCreatedEvent::class,
            [ CoreEventSubscriber::class, 'dispatchMonthReportUpdate' ]
        );

        $event->listen(
            DashboardDayAfterUpdatedEvent::class,
            [ CoreEventSubscriber::class, 'dispatchMonthReportUpdate' ]
        );
    }

    public static function dispatchMonthReportUpdate( $event )
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
