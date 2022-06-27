<?php

namespace App\Events;

use App\Models\Expense;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\SerializesModels;

class ExpenseBeforeUpdateEvent
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
        $this->expense = $expense;
        $this->request = $request;
    }
}
