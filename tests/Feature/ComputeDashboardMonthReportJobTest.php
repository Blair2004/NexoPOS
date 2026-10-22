<?php

namespace Tests\Feature;

use App\Jobs\ComputeDashboardMonthReportJob;
use App\Services\ReportService;
use Mockery;
use Tests\TestCase;

class ComputeDashboardMonthReportJobTest extends TestCase
{
    public function test_monthly_dashboard_job_can_use_the_service_default_date(): void
    {
        $reportService = Mockery::mock( ReportService::class );
        $reportService->shouldReceive( 'computeDashboardMonth' )
            ->once()
            ->withNoArgs();

        ( new ComputeDashboardMonthReportJob )->handle( $reportService );
    }
}
