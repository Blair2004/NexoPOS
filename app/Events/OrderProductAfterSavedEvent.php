<?php

namespace App\Events;

use App\Models\Order;
use App\Models\OrderProduct;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderProductAfterSavedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $product;

    public $postData;

    public $order;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct( OrderProduct $product, Order $order, array $postData )
    {
        $this->product = $product;
        $this->postData = $postData;
        $this->order = $order;
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
