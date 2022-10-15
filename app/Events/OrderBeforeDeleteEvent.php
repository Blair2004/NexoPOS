<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
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
