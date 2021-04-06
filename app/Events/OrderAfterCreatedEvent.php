<?php
namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class OrderAfterCreatedEvent implements ShouldBroadcast
{
    use SerializesModels;

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
        return new PrivateChannel( 'ns.main-socket' );
    }
}