<?php

namespace App\Events;

use App\Models\Expense;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ExpenseAfterCreateEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $expense;

    public $inputs;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct( Expense $expense, array $inputs )
    {
        $this->expense = $expense;
        $this->inputs = $inputs;
    }
}
