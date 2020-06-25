<?php 
namespace App\Events;

use App\Models\Order;
use Illuminate\Queue\SerializesModels;
use App\Models\OrderProduct;

class OrderBeforeDeleteProductEvent
{
    use SerializesModels;

    public function __construct( Order $order, OrderProduct $orderProduct )
    {
        $this->order            =   $order;
        $this->orderProduct     =   $orderProduct;
    }
}