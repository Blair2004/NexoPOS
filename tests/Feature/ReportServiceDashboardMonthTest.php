<?php

namespace Tests\Feature;

use App\Models\DashboardDay;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReportServiceDashboardMonthTest extends TestCase
{
    use DatabaseTransactions;

    public function test_month_report_uses_one_day_report_query_and_preserves_totals(): void
    {
        $this->createDayReport( '2026-08-01', 10, 1 );
        $this->createDayReport( '2026-08-02', 20, 2 );

        $dayReportQueries = 0;

        DB::listen( function ( $query ) use ( &$dayReportQueries ): void {
            if ( str_contains( $query->sql, 'nexopos_dashboard_days' ) ) {
                $dayReportQueries++;
            }
        } );

        $report = app( ReportService::class )->computeDashboardMonth( Carbon::parse( '2026-08-02' ) );

        $this->assertSame( 1, $dayReportQueries );
        $this->assertSame( 30.0, (float) $report->month_paid_orders );
        $this->assertSame( 3, (int) $report->month_paid_orders_count );
        $this->assertSame( 20.0, (float) $report->total_paid_orders );
        $this->assertSame( 2, (int) $report->total_paid_orders_count );
    }

    private function createDayReport( string $date, float $paidOrders, int $paidOrdersCount ): void
    {
        DashboardDay::withoutEvents( function () use ( $date, $paidOrders, $paidOrdersCount ): void {
            $day = new DashboardDay;
            $day->range_starts = Carbon::parse( $date )->startOfDay()->toDateTimeString();
            $day->range_ends = Carbon::parse( $date )->endOfDay()->toDateTimeString();
            $day->day_of_year = Carbon::parse( $date )->dayOfYear;
            $day->day_paid_orders = $paidOrders;
            $day->day_paid_orders_count = $paidOrdersCount;
            $day->total_paid_orders = $paidOrders;
            $day->total_paid_orders_count = $paidOrdersCount;
            $day->save();
        } );
    }
}
