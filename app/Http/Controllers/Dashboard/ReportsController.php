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
        return $this->ordersService
            ->getSoldStock( 
                $request->input( 'startDate' ), 
                $request->input( 'endDate' ) 
            );
    }
}
