<?php

namespace App\Jobs;

use App\Services\NotificationService;
use App\Services\ReportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ComputeYearlyReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $year;

    public $reportService;

    public $notificationService;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct( $year )
    {
        $this->year = $year;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle( ReportService $reportService, NotificationService $notificationService )
    {
        $reportService->computeYearReport( (int) $this->year );

        $notificationService->create( [
            'title' => __( 'Report Refreshed' ),
            'identifier' => 'ns-refreshed-annual-' . $this->year,
            'description' => sprintf(
                __( 'The yearly report has been successfully refreshed for the year "%s".' ),
                $this->year
            ),
            'url' => route( ns()->routeName( 'ns.dashboard.reports-annual' ) ),
        ] )->dispatchForGroup( [
            'admin',
            'nexopos.store.administrator',
        ] );
    }
}
