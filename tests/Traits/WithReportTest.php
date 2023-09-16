<?php

namespace Tests\Traits;

use App\Models\TaxGroup;
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
            '/dashboard/reports/transactions',
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
            ->json( 'POST', 'api/reports/sale-report', [
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
        $newReport = new \stdClass;

        /**
         * Step 1: attempt simple sale
         */
        $this->processOrders( [], function( $response, $responseData ) use ( $report, &$newReport ) {
            $newReport = $this->getSaleReport();

            $this->assertEquals(
                ns()->currency->getRaw( $report->summary->total ),
                ns()->currency->getRaw( $newReport->summary->total - $responseData[ 'data' ][ 'order' ][ 'total' ] ),
                'Order total doesn\'t match the report total.'
            );

            $this->assertEquals(
                ns()->currency->getRaw( $report->summary->sales_discounts ),
                ns()->currency->getRaw( $newReport->summary->sales_discounts - $responseData[ 'data' ][ 'order' ][ 'discount' ] ),
                'Discount total doesn\'t match the report discount.'
            );

            $this->assertEquals(
                ns()->currency->getRaw( $report->summary->subtotal ),
                ns()->currency->getRaw( $newReport->summary->subtotal - $responseData[ 'data' ][ 'order' ][ 'subtotal' ] ),
                'The subtotal doesn\'t match the report subtotal.'
            );

            $this->assertEquals(
                ns()->currency->getRaw( $report->summary->sales_taxes ),
                ns()->currency->getRaw( $newReport->summary->sales_taxes - $responseData[ 'data' ][ 'order' ][ 'tax_value' ] ),
                'The taxes doesn\'t match the report taxes.'
            );
        });

        $report = $this->getSaleReport();

        /**
         * Step 1: attempt sale with taxes
         */
        $this->processOrders([
            'tax_type' => 'inclusive',
            'taxes' => TaxGroup::with( 'taxes' )
                ->first()
                ->taxes()
                ->get()
                ->map( function( $tax ) {
                    return [
                        'tax_name' => $tax->name,
                        'tax_id' => $tax->id,
                        'rate' => $tax->rate,
                    ];
                }),
        ], function( $response, $responseData ) use ( $newReport ) {
            $freshOne = $this->getSaleReport();

            $this->assertEquals( $freshOne->summary->total, $newReport->summary->total + $responseData[ 'data' ][ 'order' ][ 'total' ], 'New report doesn\'t reflect the sale that was made.' );
            $this->assertEquals( $freshOne->summary->sales_taxes, $newReport->summary->sales_taxes + $responseData[ 'data' ][ 'order' ][ 'tax_value' ], 'The taxes doesn\'t reflect the sale that was made.' );
            $this->assertEquals( $freshOne->summary->subtotal, $newReport->summary->subtotal + $responseData[ 'data' ][ 'order' ][ 'subtotal' ], 'The subtotal doesn\'t reflect the sale that was made.' );
            $this->assertEquals( $freshOne->summary->shipping, $newReport->summary->shipping + $responseData[ 'data' ][ 'order' ][ 'shipping' ], 'The subtotal doesn\'t reflect the sale that was made.' );
            $this->assertEquals( $freshOne->summary->sales_discounts, $newReport->summary->sales_discounts + $responseData[ 'data' ][ 'order' ][ 'discount' ], 'The discount doesn\'t reflect the sale that was made.' );
        });
    }
}
