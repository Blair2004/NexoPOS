<?php

namespace App\Events;

use App\Models\ExpenseHistory;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ExpenseHistoryBeforeDeleteEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $expenseHistory;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct( ExpenseHistory $expenseHistory )
    {
        $this->expenseHistory   =   $expenseHistory;
    }
}
