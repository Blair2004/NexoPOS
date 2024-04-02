<?php

namespace App\Events;

use App\Models\TransactionHistory;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TransactionsHistoryAfterCreatedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct( public TransactionHistory $transactionHistory )
    {
        // ...
    }
}
