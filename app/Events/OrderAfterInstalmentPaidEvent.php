<?php

namespace App\Events;

use App\Models\Order;
use App\Models\OrderInstalment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderAfterInstalmentPaidEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var Order
     */
    public $order;

    /**
     * @var OrderInstalment
     */
    public $instalment;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct( OrderInstalment $instalment, Order $order )
    {
        $this->instalment   =   $instalment;
        $this->order        =   $order;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
