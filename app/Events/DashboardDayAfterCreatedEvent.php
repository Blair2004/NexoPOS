<?php

namespace App\Events;

use App\Models\DashboardDay;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DashboardDayAfterCreatedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $dashboardDay;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct( DashboardDay $dashboardDay )
    {
        $this->dashboardDay     =   $dashboardDay;
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
