<?php

namespace Tests\Traits;

use App\Services\ReportService;

trait WithReportTest
{
    use WithOrderTest;

    protected function attemptSeeReports()
    {
        $reports = [
            '/dashboard/reports/sales',
            '/dashboard/reports/sales-progress',
            '/dashboard/reports/low-stock',
            '/dashboard/reports/sold-stock',
            '/dashboard/reports/profit',
            '/dashboard/reports/cash-flow',
            '/dashboard/reports/annual-report',
            '/dashboard/reports/payment-types',
        ];

        foreach ( $reports as $report ) {
            $response = $this->withSession( $this->app[ 'session' ]->all() )
                ->json( 'GET', $report );

            $response->assertStatus(200);
        }
    }

    protected function attemptRefreshReportForPastDays()
    {
        /**
         * @var ReportService
         */
        $service = app()->make( ReportService::class );
        $startInterval = ns()->date->clone()->subDays( $this->totalDaysInterval )->subDay();

        for ( $i = 0; $i <= $this->totalDaysInterval; $i++ ) {
            $today = $startInterval->addDay()->clone();

            $service->computeDayReport(
                $today->startOfDay()->toDateTimeString(),
                $today->endOfDay()->toDateTimeString()
            );
        }

        $this->assertTrue( true );
    }

    private function getSaleReport()
    {
        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/reports/sale-report', [
                'startDate' => ns()->date->startOfDay()->toDateTimeString(),
                'endDate' => ns()->date->endOfDay()->toDateTimeString(),
                'type' => 'categories_report',
            ]);

        $response->assertOk();

        return json_decode( $response->getContent() );
    }

    protected function attemptTestSaleReport()
    {
        $report = $this->getSaleReport();

        /**
         * Step 1: attempt simple sale
         */
        $this->totalDaysInterval = 1;
        $this->useDiscount = false;
        $this->count = 1;
        $this->shouldRefund = false;
        $this->customDate = false;

        $responses = $this->attemptPostOrder( function ( $response, $responseData ) {
            // ...
        });

        $newReport = $this->getSaleReport();

        $this->assertEquals(
            ns()->currency->getRaw( $report->summary->total ),
            ns()->currency->getRaw( $newReport->summary->total - $responses[0][0][ 'order-creation' ][ 'data' ][ 'order' ][ 'total' ] ),
            'Order total doesn\'t match the report total.'
        );

        $this->assertEquals(
            ns()->currency->getRaw( $report->summary->sales_discounts ),
            ns()->currency->getRaw( $newReport->summary->sales_discounts - $responses[0][0][ 'order-creation' ][ 'data' ][ 'order' ][ 'discount' ] ),
            'Discount total doesn\'t match the report discount.'
        );

        $this->assertEquals(
            ns()->currency->getRaw( $report->summary->subtotal ),
            ns()->currency->getRaw( $newReport->summary->subtotal - $responses[0][0][ 'order-creation' ][ 'data' ][ 'order' ][ 'subtotal' ] ),
            'The subtotal doesn\'t match the report subtotal.'
        );

        $this->assertEquals(
            ns()->currency->getRaw( $report->summary->sales_taxes ),
            ns()->currency->getRaw( $newReport->summary->sales_taxes - $responses[0][0][ 'order-creation' ][ 'data' ][ 'order' ][ 'tax_value' ] ),
            'The taxes doesn\'t match the report taxes.'
        );

        /**
         * Step 1: attempt sale with taxes
         */
        $this->totalDaysInterval = 1;
        $this->useDiscount = false;
        $this->count = 1;
        $this->shouldRefund = false;

        $responses = $this->attemptPostOrder( function ( $response, $responseData ) {
            // ...
        });
    }
}
