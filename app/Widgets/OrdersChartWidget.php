<?php

namespace App\Widgets;

use App\Services\WidgetService;

class OrdersChartWidget extends WidgetService
{
    protected $vueComponent = 'nsOrdersChart';

    public function __construct()
    {
        $this->name = __( 'Orders Chart' );
        $this->description = __( 'Will display a chart of weekly sales.' );
        $this->permission = 'nexopos.see.orders-chart-widget';
    }
}
