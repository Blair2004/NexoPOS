<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;

class TransactionsHistoryAfterDeletedEvent
{
    use Dispatchable, InteractsWithSockets;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(
        public $transaction
    ) {
        // ...
    }
}
