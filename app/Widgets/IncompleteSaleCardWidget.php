<?php

namespace App\Widgets;

use App\Services\WidgetService;

class IncompleteSaleCardWidget extends WidgetService
{
    protected $vueComponent = 'nsIncompleteSaleCardWidget';

    public function __construct()
    {
        $this->name = __( 'Incomplete Sale Card Widget' );
        $this->description = __( 'Will display a card of current and overall incomplete sales.' );
        $this->permission = 'nexopos.see.incomplete-sale-card-widget';
    }
}
