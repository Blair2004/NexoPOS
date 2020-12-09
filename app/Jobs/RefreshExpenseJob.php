<?php

namespace App\Jobs;

use App\Models\DashboardDay;
use App\Services\ReportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RefreshExpenseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $dashboardDay;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct( DashboardDay $dashboardDay )
    {
        $this->dashboardDay     =   $dashboardDay;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        /**
         * @var ReportService
         */
        $reportService  =   app()->make( ReportService::class );

        $reportService->refreshFromDashboardDay( $this->dashboardDay );

        /**
         * as it's not saved from that "refreshFromDashboardDay".
         */
        $this->dashboardDay->save();
    }
}
