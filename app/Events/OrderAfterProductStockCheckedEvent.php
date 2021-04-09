<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderAfterProductStockCheckedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $items;
    public $session_identifier;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct( $items, $session_identifier )
    {
        $this->items                =   $items;
        $this->session_identifier   =   $session_identifier;
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
