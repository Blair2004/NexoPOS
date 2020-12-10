<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\DashboardController;
use App\Models\DashboardDay;
use App\Models\ExpenseCategory;
use App\Services\OrdersService;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportsController extends DashboardController
{
    /**
     * @var OrdersService
     */
    protected $orderService;

    /**
     * @var ReportService
     */
    protected $reportService;

    public function __construct(
        OrdersService $orderService,
        ReportService $reportService
    )
    {
        parent::__construct();

        $this->ordersService    =   $orderService;
        $this->reportService    =   $reportService;
    }

    public function salesReport()
    {
        return $this->view( 'pages.dashboard.reports.sales-report', [
            'title'         =>  __( 'Sales Report' ),
            'description'   =>  __( 'Provides an overview over the sales during a specific period' )
        ]);
    }
    
    public function soldStock()
    {
        return $this->view( 'pages.dashboard.reports.sold-stock-report', [
            'title'         =>  __( 'Sold Stock' ),
            'description'   =>  __( 'Provides an overview over the sold stock during a specific period.' )
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
        return $this->ordersService
            ->getPaidSales( 
                $request->input( 'startDate' ), 
                $request->input( 'endDate' ) 
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
        $startOfDay     =   Carbon::parse( $request->input( 'startDate' ) )
            ->startOfDay()
            ->toDateTimeString();

        $endOfDay       =   Carbon::parse( $request->input( 'endDate' ) )
            ->endOfDay()
            ->toDateTimeString();

        $entries        =   $this->reportService->getFromTimeRange( $startOfDay, $endOfDay );
        $total          =   $entries->first()->toArray();
        $expenses       =   ExpenseCategory::with([
            'expenses' => function( $query ) use ( $startOfDay, $endOfDay ) {
                $query->where( 'created_at', '>=', $startOfDay )
                    ->where( 'created_at', '<=', $endOfDay );
            }
        ])  
        ->get()
        ->map( function( $expenseCategory ) {
            $expenseCategory->total     =   $expenseCategory->expenses->count() > 0 ? $expenseCategory->expenses->sum( 'value' ) : 0;
            return $expenseCategory;
        });

        return [
            'summary'   =>  collect( $total )->mapWithKeys( function( $value, $key ) use( $entries ) {
                if ( ! in_array( $key, [ 'range_starts', 'range_ends', 'day_of_year' ] ) ) {
                    return [ $key => $entries->sum( $key ) ];
                }
    
                return [ $key => $value ];
            }),
            'total_debit'   =>  collect([
                $expenses->sum( 'total' ),
                $entries->sum( 'total_taxes' ),
                $entries->sum( 'total_wasted_goods' ),
                $entries->sum( 'total_discounts' )
            ])->sum(),
            'total_credit'  =>  collect([
                $entries->sum( 'total_paid_orders' ),
            ])->sum(),
            'expenses'  =>  $expenses
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
}
