<?php

namespace App\Events;

use App\Models\Order;
use App\Models\OrderProduct;
use Illuminate\Queue\SerializesModels;

class OrderBeforeDeleteProductEvent
{
    use SerializesModels;

    public function __construct( Order $order, OrderProduct $orderProduct )
    {
        $this->order = $order;
        $this->orderProduct = $orderProduct;
    }
}
