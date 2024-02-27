<?php

namespace App\Crud;

use App\Models\Order;

class UnpaidOrderCrud extends HoldOrderCrud
{
    /**
     * Define Constructor
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function hook( $query ): void
    {
        $query->orderBy( 'created_at', 'desc' );
        $query->where( 'payment_status', Order::PAYMENT_UNPAID );
    }
}
