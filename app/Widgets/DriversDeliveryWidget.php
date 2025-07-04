<?php
namespace App\Widgets;

use App\Services\WidgetService;

class DriversDeliveryWidget extends WidgetService
{
    protected $vueComponent = 'DriversDeliveryWidget';

    public function __construct()
    {
        $this->permission = 'nexopos.deliver.orders';
        $this->name = __( 'Latest Pending Deliveries' );
        $this->description = __( 'Lists the latest pending deliveries for the driver.' );
    }
}
