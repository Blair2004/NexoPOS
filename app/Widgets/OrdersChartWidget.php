<?php

namespace App\Widgets;

use App\Services\WidgetService;

class OrdersChartWidget extends WidgetService
{
    protected $vueComponent = 'nsOrdersChart';

    protected string $layout = '2x2';

    protected string $layoutPolicy = 'unrestricted';

    public function __construct()
    {
        $this->name = __( 'Orders Chart' );
        $this->description = __( 'Will display a chart of weekly sales.' );
        $this->permission = 'nexopos.see.orders-chart-widget';
    }
}
