<?php
namespace Tests\Traits;

use App\Services\ReportService;

trait WithReportTest
{
    protected function attemptSeeReports()
    {
        $reports    =   [
            '/dashboard/reports/sales',
            '/dashboard/reports/sales-progress',
            '/dashboard/reports/low-stock',
            '/dashboard/reports/sold-stock',
            '/dashboard/reports/profit',
            '/dashboard/reports/cash-flow',
            '/dashboard/reports/annual-report',
            '/dashboard/reports/payment-types',
        ];

        foreach( $reports as $report ) {
            $response       =   $this->withSession( $this->app[ 'session' ]->all() )
                ->json( 'GET', $report );

            $response->assertStatus(200);
        }
    }

    protected function attemptRefreshReportForPastDays()
    {
        /**
         * @var ReportService
         */
        $service        =   app()->make( ReportService::class );
        $startInterval  =   ns()->date->clone()->subDays( $this->totalDaysInterval )->subDay();

        for( $i = 0; $i <= $this->totalDaysInterval; $i++ ) {
            $today      =   $startInterval->addDay()->clone();

            $service->computeDayReport(
                $today->startOfDay()->toDateTimeString(),
                $today->endOfDay()->toDateTimeString()
            );
        }

        $this->assertTrue( true );    
    }
}