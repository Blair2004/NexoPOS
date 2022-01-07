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

    public $range_starts;
    public $range_ends;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct( $range_starts, $range_ends )
    {
        $this->range_ends       =   $range_ends;
        $this->range_starts     =   $range_starts;
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

        $dashboardDay   =   DashboardDay::from( $this->range_starts )->to( $this->range_ends )->first();

        if ( $dashboardDay instanceof DashboardDay ) {
            $reportService->refreshFromDashboardDay( $dashboardDay );
    
            /**
             * as it's not saved from that "refreshFromDashboardDay".
             */
            DashboardDay::withoutEvents( function() use ( $dashboardDay ) {
                $dashboardDay->save();
            });
        }

    }
}
