<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AfterMigrationExecutedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $module;
    public $response;
    public $file;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct( $module, $response, $file )
    {
        $this->module       =   $module;  
        $this->response     =   $response;
        $this->file         =   $file;
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
