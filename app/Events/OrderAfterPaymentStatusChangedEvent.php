<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderAfterPaymentStatusChangedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $order;
    public $previous;
    public $new;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct( Order $order, $previous, $new )
    {
        $this->order        =   $order;
        $this->previous     =   $previous;
        $this->new          =   $new;
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
