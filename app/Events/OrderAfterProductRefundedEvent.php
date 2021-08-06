<?php
namespace App\Events;

use App\Models\Order;
use Illuminate\Queue\SerializesModels;
use App\Models\OrderProduct;
use App\Models\OrderProductRefund;
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

    /**
     * @var OrderProductRefund
     */
    public $orderProductRefund;

    public function __construct( Order $order, OrderProduct $orderProduct, OrderProductRefund $orderProductRefund )
    {
        $this->order                =   $order;
        $this->orderProduct         =   $orderProduct;
        $this->orderProductRefund   =   $orderProductRefund;
    }
} 