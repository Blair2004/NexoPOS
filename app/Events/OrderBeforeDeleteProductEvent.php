<?php

namespace App\Events;

use App\Models\Order;
use App\Models\OrderProduct;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderBeforeDeleteProductEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct( public Order $order, public OrderProduct $orderProduct )
    {
        // ...
    }
}
