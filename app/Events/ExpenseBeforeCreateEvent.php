<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\SerializesModels;

class ExpenseBeforeCreateEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param Request
     */
    public $inputs;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct( array $inputs )
    {
        $this->inputs  =   $inputs;
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
