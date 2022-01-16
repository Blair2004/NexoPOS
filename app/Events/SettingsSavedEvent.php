<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SettingsSavedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $options;
    public $inputs;
    public $class;

    /**
     * Create a new event instance.
     *
     * @return void
     */

    public function __construct( $options, $inputs, $class )
    {
        $this->options      =   $options;
        $this->inputs       =   $inputs;
        $this->class        =   $class;
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
