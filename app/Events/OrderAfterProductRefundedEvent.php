<?php
namespace App\Events;

use App\Models\Order;
use Illuminate\Queue\SerializesModels;
use App\Models\OrderProduct;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Bus\Dispatchable;

class OrderAfterProductRefundedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var Order
     */
    public $order;

    /**
     * @var OrderProduct
     */
    public $orderProduct;

    public function __construct( Order $order, OrderProduct $orderProduct )
    {
        $this->order            =   $order;
        $this->orderProduct     =   $orderProduct;
    }
} 