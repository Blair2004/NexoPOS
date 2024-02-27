<?php

namespace App\Widgets;

use App\Services\WidgetService;

class ExpenseCardWidget extends WidgetService
{
    protected $vueComponent = 'nsExpenseCardWidget';

    public function __construct()
    {
        $this->name = __( 'Expense Card Widget' );
        $this->description = __( 'Will display a card of current and overwall expenses.' );
        $this->permission = 'nexopos.see.expense-card-widget';
    }
}
