<?php

namespace Tests\Feature;

use App\Models\DashboardMonth;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReportServiceYearReportTest extends TestCase
{
    use DatabaseTransactions;

    public function test_year_report_loads_existing_months_with_one_lookup(): void
    {
        foreach ( range( 1, 12 ) as $month ) {
            DashboardMonth::withoutEvents( function () use ( $month ): void {
                $date = Carbon::create( 2025, $month, 1 );
                $report = new DashboardMonth;
                $report->month_of_year = $month;
                $report->range_starts = $date->copy()->startOfMonth()->toDateTimeString();
                $report->range_ends = $date->copy()->endOfMonth()->toDateTimeString();
                $report->total_income = $month;
                $report->save();
            } );
        }

        DB::enableQueryLog();
        DB::flushQueryLog();

        $reports = app( ReportService::class )->getYearReportFor( 2025 );

        $monthLookups = array_filter( DB::getQueryLog(), fn( array $query ): bool => str_contains( $query['query'], 'nexopos_dashboard_months' ) );

        $this->assertCount( 1, $monthLookups );
        $this->assertCount( 12, $reports );
        $this->assertSame( 1.0, (float) $reports[1]->total_income );
        $this->assertSame( 12.0, (float) $reports[12]->total_income );
    }
}
