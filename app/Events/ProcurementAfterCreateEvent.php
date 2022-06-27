<?php

namespace App\Events;

use App\Models\Procurement;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProcurementAfterCreateEvent
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
        $this->procurement = $procurement;
    }
}
