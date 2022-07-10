<?php

namespace App\Events;

use App\Models\OrderProduct;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderProductBeforeSavedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $orderProduct;

    public $data;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct( OrderProduct $orderProduct, $data )
    {
        $this->orderProduct = $orderProduct;
        $this->data = $data;
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
