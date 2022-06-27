<?php

namespace App\Jobs;

use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RefreshReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $event;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct( $event )
    {
        $this->event = $event;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle( ReportService $reportService )
    {
        $date = Carbon::parse( $this->event->cashFlow->created_at );

        $reportService->computeDayReport(
            dateStart: $date->startOfDay()->toDateTimeString(),
            dateEnd: $date->endOfDay()->toDateTimeString()
        );
    }
}
