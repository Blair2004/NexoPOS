<?php

namespace App\Events;

use App\Models\Order;
use App\Traits\NsSerialize;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderAfterCreatedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, NsSerialize;

    public function __construct( public Order $order, public $fields )
    {
        $this->prepareSerialization();
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel( 'ns.private-channel' );
    }
}
