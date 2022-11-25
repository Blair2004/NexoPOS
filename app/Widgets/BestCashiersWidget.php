<?php
namespace App\Widgets;

use App\Services\WidgetService;

class BestCashiersWidget extends WidgetService
{
    protected $vueComponent     =   'nsBestCashiers';

    public function __construct()
    {
        $this->name     =   __( 'Best Cashiers' );
    }
}