<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\DashboardController;
use App\Services\OrdersService;
use Illuminate\Http\Request;

class ReportsController extends DashboardController
{
    /**
     * @var OrdersService
     */
    protected $orderService;

    public function __construct(
        OrdersService $orderService
    )
    {
        parent::__construct();
        $this->ordersService    =   $orderService;
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
    
    /**
     * get sold stock on a specific time range
     * @param Request $request
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
