<?php

namespace App\Widgets;

use App\Services\WidgetService;

class OrdersSummaryWidget extends WidgetService
{
    protected $vueComponent = 'nsOrdersSummary';

    protected string $layout = '1x2';

    protected string $layoutPolicy = 'restricted';

    protected array $supportedLayouts = [ '1x2', '1x3', '1x4', '1x5', '2x2', '2x3', '2x4', '2x5' ];

    public function __construct()
    {
        $this->name = __( 'Orders Summary' );
        $this->description = __( 'Will display a summary of recent sales.' );
        $this->permission = 'nexopos.see.orders-summary-widget';
    }
}
