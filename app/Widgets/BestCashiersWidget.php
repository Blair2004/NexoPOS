<?php

namespace App\Widgets;

use App\Services\WidgetService;

class BestCashiersWidget extends WidgetService
{
    protected $vueComponent = 'nsBestCashiers';

    protected string $layout = '1x2';

    protected string $layoutPolicy = 'restricted';

    protected array $supportedLayouts = [ '1x2', '1x3', '1x4', '1x5', '2x2', '2x3', '2x4', '2x5' ];

    public function __construct()
    {
        $this->name = __( 'Best Cashiers' );
        $this->description = __( 'Will display all cashiers who performs well.' );
        $this->permission = 'nexopos.see.best-cashier-widget';
    }
}
