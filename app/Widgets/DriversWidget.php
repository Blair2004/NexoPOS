<?php
namespace App\Widgets;

use App\Services\WidgetService;

class DriversWidget extends WidgetService
{
    protected $vueComponent = 'DriversWidgetComponent';

    public function __construct()
    {
        $this->name = __( 'Drivers Widget' );
        $this->description = __( 'Displays driver-related information and actions.' );
        $this->permission = 'nexopos.deliver.orders';
    }
}
