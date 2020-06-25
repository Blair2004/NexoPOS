<?php
namespace App\Events;

use App\Models\Order;
use Illuminate\Queue\SerializesModels;
use App\Models\OrderProduct;

class OrderAfterProductRefundedEvent
{
    use SerializesModels;
    public $order;
    public $orderProduct;

    public function __constructor( Order $order, OrderProduct $orderProduct )
    {
        $this->order            =   $order;
        $this->orderProduct     =   $orderProduct;
    }
} 