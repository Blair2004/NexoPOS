<?php

namespace App\Widgets;

use App\Services\WidgetService;

class OrdersChartWidget extends WidgetService
{
    protected $vueComponent = 'nsOrdersChart';

    protected string $layout = '2x3';

    protected string $layoutPolicy = 'unrestricted';

    protected array $supportedLayouts = [ '1x3', '2x3' ];

    public function __construct()
    {
        $this->name = __( 'Orders Chart' );
        $this->description = __( 'Will display a chart of weekly sales.' );
        $this->permission = 'nexopos.see.orders-chart-widget';
    }
}
