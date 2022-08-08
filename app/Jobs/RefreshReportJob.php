<?php

namespace App\Jobs;

use App\Models\CashFlow;
use App\Services\ReportService;
use App\Traits\NsSerialize;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RefreshReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, NsSerialize;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct( public CashFlow $cashFlow )
    {
        $this->prepareSerialization();
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
