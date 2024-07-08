<?php

namespace Tests\Traits;

use App\Models\ProductCategory;
use App\Models\TaxGroup;
use App\Services\ReportService;
use Illuminate\Support\Facades\Auth;

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

            $response->assertStatus( 200 );

            gc_collect_cycles();
        }
    }

    protected function attemptLoadReports()
    {
        $reports = [
            /**
             * for the annual report
             */
            [
                'url' => 'annual-report',
                'data' => [
                    'year' => now()->format( 'Y' ),
                ],
            ],

            /**
             * This combines all sale-report reports variations.
             */
            [
                'url' => 'sale-report',
                'data' => [
                    'startDate' => now()->startOfDay()->toDateTimeString(),
                    'endDate' => now()->endOfDay()->toDateTimeString(),
                    'type' => 'products_report',
                    'user_id' => Auth::id(),
                    'categories_id' => [ ProductCategory::first()->id ],
                ],
            ],
            [
                'url' => 'sale-report',
                'data' => [
                    'startDate' => now()->startOfDay()->toDateTimeString(),
                    'endDate' => now()->endOfDay()->toDateTimeString(),
                    'type' => 'categories_report',
                    'user_id' => Auth::id(),
                    'categories_id' => [ ProductCategory::first()->id ],
                ],
            ],
            [
                'url' => 'sale-report',
                'data' => [
                    'startDate' => now()->startOfDay()->toDateTimeString(),
                    'endDate' => now()->endOfDay()->toDateTimeString(),
                    'type' => 'categories_summary',
                    'user_id' => Auth::id(),
                    'categories_id' => [ ProductCategory::first()->id ],
                ],
            ],
        ];

        foreach ( $reports as $report ) {
            $response = $this->withSession( $this->app[ 'session' ]->all() )
                ->json( 'POST', 'api/reports/' . $report[ 'url' ], $report[ 'data' ] );

            if ( $response->status() !== 200 ) {
                $response->dump();
            }

            $response->assertOk();
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
            ] );

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
        $this->processOrders( [], function ( $response, $responseData ) use ( $report, &$newReport ) {
            $newReport = $this->getSaleReport();

            $this->assertEquals(
                ns()->currency->define( $report->summary->total )->toFloat(),
                ns()->currency->define( $newReport->summary->total )->subtractBy( $responseData[ 'data' ][ 'order' ][ 'total' ] )->toFloat(),
                'Order total doesn\'t match the report total.'
            );

            $this->assertEquals(
                ns()->currency->define( $report->summary->sales_discounts )->toFloat(),
                ns()->currency->define( $newReport->summary->sales_discounts )->subtractBy( $responseData[ 'data' ][ 'order' ][ 'discount' ] )->toFloat(),
                'Discount total doesn\'t match the report discount.'
            );

            $this->assertEquals(
                ns()->currency->define( $report->summary->subtotal )->toFloat(),
                ns()->currency->define( $newReport->summary->subtotal )->subtractBy( $responseData[ 'data' ][ 'order' ][ 'subtotal' ] )->toFloat(),
                'The subtotal doesn\'t match the report subtotal.'
            );

            $this->assertEquals(
                ns()->currency->define( $report->summary->sales_taxes )->toFloat(),
                ns()->currency->define( $newReport->summary->sales_taxes )->subtractBy( $responseData[ 'data' ][ 'order' ][ 'tax_value' ] )->toFloat(),
                'The taxes doesn\'t match the report taxes.'
            );
        } );

        $report = $this->getSaleReport();

        /**
         * Step 1: attempt sale with taxes
         */
        $this->processOrders( [
            'tax_type' => 'inclusive',
            'taxes' => TaxGroup::with( 'taxes' )
                ->first()
                ->taxes()
                ->get()
                ->map( function ( $tax ) {
                    return [
                        'name' => $tax->name,
                        'tax_id' => $tax->id,
                        'rate' => $tax->rate,
                    ];
                } ),
        ], function ( $response, $responseData ) use ( $newReport ) {
            $freshOne = $this->getSaleReport();

            $this->assertEquals( $freshOne->summary->total, ns()->currency->define( $newReport->summary->total )->additionateBy( $responseData[ 'data' ][ 'order' ][ 'total' ] )->toFloat(), 'New report doesn\'t reflect the sale that was made.' );
            $this->assertEquals( $freshOne->summary->sales_taxes, ns()->currency->define( $newReport->summary->sales_taxes )->additionateBy( $responseData[ 'data' ][ 'order' ][ 'tax_value' ] )->toFloat(), 'The taxes doesn\'t reflect the sale that was made.' );
            $this->assertEquals( $freshOne->summary->subtotal, ns()->currency->define( $newReport->summary->subtotal )->additionateBy( $responseData[ 'data' ][ 'order' ][ 'subtotal' ] )->toFloat(), 'The subtotal doesn\'t reflect the sale that was made.' );
            $this->assertEquals( $freshOne->summary->shipping, ns()->currency->define( $newReport->summary->shipping )->additionateBy( $responseData[ 'data' ][ 'order' ][ 'shipping' ] )->toFloat(), 'The subtotal doesn\'t reflect the sale that was made.' );
            $this->assertEquals( $freshOne->summary->sales_discounts, ns()->currency->define( $newReport->summary->sales_discounts )->additionateBy( $responseData[ 'data' ][ 'order' ][ 'discount' ] )->toFloat(), 'The discount doesn\'t reflect the sale that was made.' );
        } );
    }
}
