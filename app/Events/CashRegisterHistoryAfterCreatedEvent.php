<?php

namespace App\Events;

use App\Models\RegisterHistory;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CashRegisterHistoryAfterCreatedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct( public RegisterHistory $registerHistory )
    {
        // ...
    }
}
