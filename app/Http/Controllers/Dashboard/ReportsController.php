<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\DashboardController;
use App\Jobs\ComputeYearlyReportJob;
use App\Models\AccountType;
use App\Models\CashFlow;
use App\Models\DashboardDay;
use App\Models\ExpenseCategory;
use App\Services\OrdersService;
use App\Services\ReportService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportsController extends DashboardController
{
    /**
     * @var OrdersService
     */
    protected $ordersService;

    /**
     * @var ReportService
     */
    protected $reportService;

    public function __construct(
        OrdersService $ordersService,
        ReportService $reportService
    )
    {
        parent::__construct();

        $this->ordersService    =   $ordersService;
        $this->reportService    =   $reportService;
    }

    public function salesReport()
    {
        return $this->view( 'pages.dashboard.reports.sales-report', [
            'title'         =>  __( 'Sales Report' ),
            'description'   =>  __( 'Provides an overview over the sales during a specific period' )
        ]);
    }

    public function salesProgress()
    {
        return $this->view( 'pages.dashboard.reports.best-products-report', [
            'title'         =>  __( 'Sales Progress' ),
            'description'   =>  __( 'Provides an overview over the best products sold during a specific period.' )
        ]);
    }
    
    public function soldStock()
    {
        return $this->view( 'pages.dashboard.reports.sold-stock-report', [
            'title'         =>  __( 'Sold Stock' ),
            'description'   =>  __( 'Provides an overview over the sold stock during a specific period.' )
        ]);
    }

    public function lowStockReport()
    {
        return $this->view( 'pages.dashboard.reports.low-stock-report', [
            'title'         =>  __( 'Low Stock Report' ),
            'description'   =>  __( 'Provides an overview of the product which stock are low.' )
        ]);
    }

    public function profit()
    {
        return $this->view( 'pages.dashboard.reports.profit-report', [
            'title'         =>  __( 'Profit Report' ),
            'description'   =>  __( 'Provides an overview of the provide of the products sold.' )
        ]);
    }

    public function cashFlow()
    {
        return $this->view( 'pages.dashboard.reports.cash-flow', [
            'title'         =>  __( 'Cash Flow Report' ),
            'description'   =>  __( 'Provides an overview on the activity for a specific period.' )
        ]);
    }

    /**
     * get sales based on a specific time range
     * @param Request $request
     * @return array
     */
    public function getSaleReport( Request $request )
    {
        return $this->reportService
            ->getSaleReport( 
                $request->input( 'startDate' ), 
                $request->input( 'endDate' ),
                $request->input( 'type' ),
                $request->input( 'user_id' )
            );
    }

    /**
     * get sold stock on a specific time range
     * @param Request $request
     * @return array
     */
    public function getSoldStockReport( Request $request )
    {
        $orders     =   $this->ordersService
            ->getSoldStock( 
                $request->input( 'startDate' ), 
                $request->input( 'endDate' ) 
            );

        return collect( $orders )->mapToGroups( function( $product ) {
            return [
                $product->product_id . '-' . $product->unit_id  =>  $product
            ];
        })->map( function( $groups ) {
            return [
                'name'          =>  $groups->first()->name,
                'unit_name'     =>  $groups->first()->unit_name,
                'mode'          =>  $groups->first()->mode,
                'unit_price'    =>  $groups->sum( 'unit_price' ),
                'quantity'      =>  $groups->sum( 'quantity' ),
                'total_price'   =>  $groups->sum( 'total_price' ),
                'tax_value'     =>  $groups->sum( 'tax_value' ),
            ];
        })->values();
    }

    public function getCashFlow( Request $request )
    {
        $rangeStarts     =   Carbon::parse( $request->input( 'startDate' ) )
            ->toDateTimeString();

        $rangeEnds       =   Carbon::parse( $request->input( 'endDate' ) )
            ->toDateTimeString();

        $entries        =   $this->reportService->getFromTimeRange( $rangeStarts, $rangeEnds );
        $total          =   $entries->count() > 0 ? $entries->first()->toArray() : [];
        $creditCashFlow =   AccountType::where( 'operation', CashFlow::OPERATION_CREDIT )->with([
            'cashFlowHistories' => function( $query ) use ( $rangeStarts, $rangeEnds ) {
                $query->where( 'created_at', '>=', $rangeStarts )
                    ->where( 'created_at', '<=', $rangeEnds );
            }
        ])  
        ->get()
        ->map( function( $accountType ) {
            $accountType->total     =   $accountType->cashFlowHistories->count() > 0 ? $accountType->cashFlowHistories->sum( 'value' ) : 0;
            return $accountType;
        });

        $debitCashFlow =   AccountType::where( 'operation', CashFlow::OPERATION_DEBIT )->with([
            'cashFlowHistories' => function( $query ) use ( $rangeStarts, $rangeEnds ) {
                $query->where( 'created_at', '>=', $rangeStarts )
                    ->where( 'created_at', '<=', $rangeEnds );
            }
        ])  
        ->get()
        ->map( function( $accountType ) {
            $accountType->total     =   $accountType->cashFlowHistories->count() > 0 ? $accountType->cashFlowHistories->sum( 'value' ) : 0;
            return $accountType;
        });

        return [
            'summary'   =>  collect( $total )->mapWithKeys( function( $value, $key ) use( $entries ) {
                if ( ! in_array( $key, [ 'range_starts', 'range_ends', 'day_of_year' ] ) ) {
                    return [ $key => $entries->sum( $key ) ];
                }
                return [ $key => $value ];
            }),
            
            'total_debit'   =>  collect([
                $debitCashFlow->sum( 'total' ),
            ])->sum(),
            'total_credit'  =>  collect([
                $creditCashFlow->sum( 'total' ),
            ])->sum(),

            'creditCashFlow'    =>  $creditCashFlow,
            'debitCashFlow'     =>  $debitCashFlow
        ];
    }
    
    /**
     * get sold stock on a specific time range
     * @param Request $request
     * @todo review
     * @return array
     */
    public function getProfit( Request $request )
    {
        $orders     =   $this->ordersService
            ->getSoldStock( 
                $request->input( 'startDate' ), 
                $request->input( 'endDate' ) 
            );

        return $orders;

        return collect( $orders )->mapToGroups( function( $product ) {
            return [
                $product->product_id . '-' . $product->unit_id  =>  $product
            ];
        })->map( function( $groups ) {
            return [
                'name'                      =>  $groups->first()->name,
                'unit_name'                 =>  $groups->first()->unit_name,
                'mode'                      =>  $groups->first()->mode,
                'unit_price'                =>  $groups->sum( 'unit_price' ),
                'total_purchase_price'      =>  $groups->sum( 'total_purchase_price' ),
                'quantity'                  =>  $groups->sum( 'quantity' ),
                'total_price'               =>  $groups->sum( 'total_price' ),
                'tax_value'                 =>  $groups->sum( 'tax_value' ),
            ];
        })->values();
    }

    public function getAnnualReport( Request $request )
    {
        return $this->reportService->getYearReportFor( $request->input( 'year' ) );
    }

    public function annualReport( Request $request )
    {
        return $this->view( 'pages.dashboard.reports.annual-report', [
            'title'         =>  __( 'Annual Report' ),
            'description'   =>  __( 'Provides an overview over the sales during a specific period' )
        ]);        
    }

    public function salesByPaymentTypes( Request $request )
    {
        return $this->view( 'pages.dashboard.reports.payment-types', [
            'title'         =>  __( 'Sales By Payment Types' ),
            'description'   =>  __( 'Provide a report of the sales by payment types, for a specific period.' )
        ]); 
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
                'stauts'    =>  'success',
                'message'   =>  __( 'The report will be computed for the current year.' )
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

    public function getMyReport( Request $request )
    {
        return $this->reportService->getCashierDashboard( Auth::id() );
    }

    public function getLowStock( Request $request )
    {
        return $this->reportService->getLowStockProducts();
    }
}
