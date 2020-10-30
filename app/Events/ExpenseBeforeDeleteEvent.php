<?php

namespace App\Events;

use App\Models\Expense;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ExpenseBeforeDeleteEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $expense;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct( Expense $expense )
    {
        $this->expense      =   $expense;
    }
}
