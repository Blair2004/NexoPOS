<?php
namespace App\Widgets;

use App\Services\WidgetService;

class DriverEarningsWidget extends WidgetService
{
    protected $vueComponent = 'DriverEarningsWidgetComponent';

    public function __construct()
    {
        $this->name = __( 'Driver Earnings Stats' );
        $this->description = __( 'Displays driver earnings including pending and approved commissions for the current month.' );
        $this->permission = 'nexopos.deliver.orders';
    }
}
