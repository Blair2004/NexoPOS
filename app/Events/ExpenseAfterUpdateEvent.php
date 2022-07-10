<?php

namespace App\Events;

use App\Models\Expense;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\SerializesModels;

class ExpenseAfterUpdateEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $request;

    public $expense;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct( Expense $expense, Request $request )
    {
        $this->request = $request;
        $this->expense = $expense;
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
