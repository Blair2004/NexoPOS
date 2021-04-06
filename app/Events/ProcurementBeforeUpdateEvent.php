<?php

namespace App\Events;

use App\Models\Procurement;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProcurementBeforeUpdateEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $procurement;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct( Procurement $procurement )
    {
        $this->procurement  =   $procurement;
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
