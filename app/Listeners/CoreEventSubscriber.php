<?php

namespace App\Listeners;

use App\Events\AfterAppHealthCheckedEvent;
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
    public function __construct()
    {
        // 
    }

    public function subscribe( $event )
    {
        $event->listen(
            AfterAppHealthCheckedEvent::class,
            [ CoreEventSubscriber::class, 'initializeJobReport' ]
        );
    }

    public function initializeJobReport()
    {
        if ( ! DashboardDay::forToday() instanceof DashboardDay ) {
            InitializeDailyReportJob::dispatch()->delay( now() );
        }
    }
}
