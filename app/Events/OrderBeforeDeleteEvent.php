<?php

namespace App\Events;

use App\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderBeforeDeleteEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct( public $order )
    {
        // ...
    }
}
