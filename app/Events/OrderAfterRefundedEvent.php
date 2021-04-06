<?php

namespace App\Events;

use App\Models\Order;
use App\Models\OrderRefund;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderAfterRefundedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $order;
    public $orderRefund;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct( Order $order, OrderRefund $orderRefund )
    {
        $this->order            =   $order;
        $this->orderRefund      =   $orderRefund;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('ns.private-channel');
    }
}
