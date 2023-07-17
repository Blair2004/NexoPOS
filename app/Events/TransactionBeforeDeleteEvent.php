<?php

namespace App\Events;

use App\Models\Transaction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * @todo We might link all cash flow to make sure
 * those are deleted when the parent transaction is deleted.
 */
class TransactionBeforeDeleteEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct( public Transaction $transaction )
    {
        // ...
    }
}
