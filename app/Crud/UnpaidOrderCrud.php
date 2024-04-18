<?php

namespace App\Crud;

use App\Models\Order;

class UnpaidOrderCrud extends HoldOrderCrud
{
    /**
     * Define the autoload status
     */
    const AUTOLOAD = true;

    /**
     * Define the identifier
     */
    const IDENTIFIER = 'ns.unpaid-orders';

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
