<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderAfterCheckPerformedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $fields;

    public $order;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct( $fields, $order )
    {
        $this->order = $order;
        $this->fields = $fields;
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
