<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\DashboardController;
use Illuminate\Http\Request;

class ReportsController extends DashboardController
{
    public function salesReport()
    {
        return $this->view( 'pages.dashboard.reports.sales-report', [
            'title'         =>  __( 'Sales Report' ),
            'description'   =>  __( 'Provides an overview over the sales during a specific period' )
        ]);
    }
}
