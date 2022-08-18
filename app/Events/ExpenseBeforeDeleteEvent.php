<?php

namespace App\Events;

use App\Models\Expense;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * @todo We might link all cash flow to make sure
 * those are deleted when the parent expense is deleted.
 */
class ExpenseBeforeDeleteEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct( public Expense $expense )
    {
        // ...
    }
}
