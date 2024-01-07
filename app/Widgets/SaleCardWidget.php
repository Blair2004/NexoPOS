<?php

namespace App\Widgets;

use App\Services\WidgetService;

class SaleCardWidget extends WidgetService
{
    protected $vueComponent = 'nsSaleCardWidget';

    public function __construct()
    {
        $this->name = __( 'Sale Card Widget' );
        $this->description = __( 'Will display current and overall sales.' );
        $this->permission = 'nexopos.see.sale-card-widget';
    }
}
