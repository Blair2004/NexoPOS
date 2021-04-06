<?php

namespace App\Events;

use App\Models\Order;
use App\Models\OrderPayment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderAfterPaymentCreatedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $orderPayment;
    public $order;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct( OrderPayment $orderPayment, Order $order )
    {
        $this->orderPayment     =   $orderPayment;
        $this->order            =   $order;
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
