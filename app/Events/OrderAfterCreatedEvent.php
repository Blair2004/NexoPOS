<?php
namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderAfterCreatedEvent implements ShouldBroadcast
{
    use SerializesModels, Dispatchable, InteractsWithSockets;

    public $order;
    public $fields;

    public function __construct( Order $order, $fields )
    {
        $this->order    =   $order;
        $this->fields   =   $fields;
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