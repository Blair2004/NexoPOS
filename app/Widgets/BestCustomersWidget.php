<?php
namespace App\Widgets;

use App\Services\WidgetService;

class BestCustomersWidget extends WidgetService
{
    protected $vueComponent     =   'nsBestCustomers';

    public function __construct()
    {
        $this->name     =   __( 'Best Customers' );
    }
}