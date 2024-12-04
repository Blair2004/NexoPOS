<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\DashboardController;
use App\Jobs\ComputeYearlyReportJob;
use App\Models\Customer;
use App\Services\DateService;
use App\Services\OrdersService;
use App\Services\ReportService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

class ReportsController extends DashboardController
{
    public function __construct(
        protected OrdersService $ordersService,
        protected ReportService $reportService,
        protected DateService $dateService
    ) {
        // ...
    }

    public function salesReport()
    {
        return View::make( 'pages.dashboard.reports.sales-report', [
            'title' => __( 'Sales Report' ),
            'description' => __( 'Provides an overview over the sales during a specific period' ),
        ] );
    }

    public function salesProgress()
    {
        return View::make( 'pages.dashboard.reports.best-products-report', [
            'title' => __( 'Sales Progress' ),
            'description' => __( 'Provides an overview over the best products sold during a specific period.' ),
        ] );
    }

    public function soldStock()
    {
        return View::make( 'pages.dashboard.reports.sold-stock-report', [
            'title' => __( 'Sold Stock' ),
            'description' => __( 'Provides an overview over the sold stock during a specific period.' ),
        ] );
    }

    public function stockReport()
    {
        return View::make( 'pages.dashboard.reports.low-stock-report', [
            'title' => __( 'Stock Report' ),
            'description' => __( 'Provides an overview of the products stock.' ),
        ] );
    }

    public function profit()
    {
        return View::make( 'pages.dashboard.reports.profit-report', [
            'title' => __( 'Profit Report' ),
            'description' => __( 'Provides an overview of the provide of the products sold.' ),
        ] );
    }

    public function transactionsReport()
    {
        return View::make( 'pages.dashboard.reports.transactions', [
            'title' => __( 'Transactions Report' ),
            'description' => __( 'Provides an overview on the activity for a specific period.' ),
        ] );
    }

    public function stockCombinedReport()
    {
        return View::make( 'pages.dashboard.reports.stock-combined', [
            'title' => __( 'Combined Report' ),
            'description' => __( 'Provides a combined report for every transactions on products.' ),
        ] );
    }

    /**
     * get sales based on a specific time range
     *
     * @return array
     */
    public function getSaleReport( Request $request )
    {
        return $this->reportService
            ->getSaleReport(
                $request->input( 'startDate' ),
                $request->input( 'endDate' ),
                $request->input( 'type' ),
                $request->input( 'user_id' ),
                $request->input( 'categories_id' ),
            );
    }

    /**
     * get sold stock on a specific time range
     *
     * @return array
     */
    public function getSoldStockReport( Request $request )
    {
        $orders = $this->ordersService
            ->getSoldStock(
                startDate: $request->input( 'startDate' ),
                endDate: $request->input( 'endDate' ),
                categories: $request->input( 'categories' ),
                units: $request->input( 'units' )
            );

        return collect( $orders )->mapToGroups( function ( $product ) {
            return [
                $product->product_id . '-' . $product->unit_id => $product,
            ];
        } )->map( function ( $groups ) {
            return [
                'name' => $groups->first()->name,
                'unit_name' => $groups->first()->unit_name,
                'mode' => $groups->first()->mode,
                'unit_price' => $groups->sum( 'unit_price' ),
                'quantity' => $groups->sum( 'quantity' ),
                'total_price' => $groups->sum( 'total_price' ),
                'tax_value' => $groups->sum( 'tax_value' ),
            ];
        } )->values();
    }

    public function getAccountSummaryReport( Request $request )
    {
        return $this->reportService->getAccountSummaryReport(
            $request->input( 'startDate' ),
            $request->input( 'endDate' ),
        );
    }

    /**
     * get sold stock on a specific time range
     *
     *
     * @todo review
     *
     * @return array
     */
    public function getProfit( Request $request )
    {
        $orders = $this->ordersService
            ->getSoldStock(
                startDate: $request->input( 'startDate' ),
                endDate: $request->input( 'endDate' ),
                categories: $request->input( 'categories' ),
                units: $request->input( 'units' )
            );

        return $orders;
    }

    public function getAnnualReport( Request $request )
    {
        return $this->reportService->getYearReportFor( $request->input( 'year' ) );
    }

    public function annualReport( Request $request )
    {
        return View::make( 'pages.dashboard.reports.annual-report', [
            'title' => __( 'Annual Report' ),
            'description' => __( 'Provides an overview over the sales during a specific period' ),
        ] );
    }

    public function salesByPaymentTypes( Request $request )
    {
        return View::make( 'pages.dashboard.reports.payment-types', [
            'title' => __( 'Sales By Payment Types' ),
            'description' => __( 'Provide a report of the sales by payment types, for a specific period.' ),
        ] );
    }

    public function getPaymentTypes( Request $request )
    {
        return $this->ordersService->getPaymentTypesReport(
            $request->input( 'startDate' ),
            $request->input( 'endDate' ),
        );
    }

    public function computeReport( Request $request, $type )
    {
        if ( $type === 'yearly' ) {
            ComputeYearlyReportJob::dispatch( $request->input( 'year' ) );

            return [
                'stauts' => 'success',
                'message' => __( 'The report will be computed for the current year.' ),
            ];
        }

        throw new Exception( __( 'Unknown report to refresh.' ) );
    }

    public function getProductsReport( Request $request )
    {
        return $this->reportService->getProductSalesDiff(
            $request->input( 'startDate' ),
            $request->input( 'endDate' ),
            $request->input( 'sort' )
        );
    }

    public function getMyReport()
    {
        return $this->reportService->getCashierDashboard( Auth::id() );
    }

    public function getLowStock( Request $request )
    {
        return $this->reportService->getLowStockProducts(
            categories: $request->input( 'categories' ),
            units: $request->input( 'units' )
        );
    }

    public function getStockReport( Request $request )
    {
        return $this->reportService->getStockReport(
            categories: $request->input( 'categories' ),
            units: $request->input( 'units' )
        );
    }

    public function showCustomerStatement()
    {
        return View::make( 'pages.dashboard.reports.customers-statement', [
            'title' => __( 'Customers Statement' ),
            'description' => __( 'Display the complete customer statement.' ),
        ] );
    }

    public function getCustomerStatement( Customer $customer, Request $request )
    {
        return $this->reportService->getCustomerStatement(
            customer: $customer,
            rangeStarts: $request->input( 'rangeStarts' ),
            rangeEnds: $request->input( 'rangeEnds' )
        );
    }

    public function getProductHistoryCombined( Request $request )
    {
        return $this->reportService->getCombinedProductHistory(
            Carbon::parse( $request->input( 'date' ) )->format( 'Y-m-d' ),
            $request->input( 'categories' ),
            $request->input( 'units' )
        );
    }

    public function computeCombinedReport( Request $request )
    {
        return $this->reportService->computeCombinedReport( $request->input( 'date' ) );
    }
}
