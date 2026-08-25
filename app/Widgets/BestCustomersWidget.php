<?php

namespace App\Widgets;

use App\Services\WidgetService;

class BestCustomersWidget extends WidgetService
{
    protected $vueComponent = 'nsBestCustomers';

    protected string $layout = '1x2';

    protected string $layoutPolicy = 'restricted';

    protected array $supportedLayouts = [ '1x2', '1x3', '1x4', '1x5', '2x2', '2x3', '2x4', '2x5' ];

    public function __construct()
    {
        $this->name = __( 'Best Customers' );
        $this->description = __( 'Will display all customers with the highest purchases.' );
        $this->permission = 'nexopos.see.best-customers-widget';
    }
}
