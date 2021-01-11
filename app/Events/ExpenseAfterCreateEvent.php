<?php

namespace App\Events;

use App\Models\Expense;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\SerializesModels;

class ExpenseAfterCreateEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $expense;
    public $request;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct( Expense $expense, Request $request )
    {
        $this->expense  =   $expense;
        $this->request  =   $request;
    }
}
