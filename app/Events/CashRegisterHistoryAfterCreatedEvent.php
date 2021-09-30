<?php

namespace App\Events;

use App\Models\RegisterHistory;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CashRegisterHistoryAfterCreatedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $registerHistory;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct( RegisterHistory $registerHistory )
    {
        $this->registerHistory      =   $registerHistory;
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
