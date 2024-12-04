<?php

namespace App\Listeners;

use App\Events\CashRegisterHistoryAfterDeletedEvent;
use App\Services\CashRegistersService;

class CashRegisterHistoryAfterDeletedEventListener
{
    /**
     * Create the event listener.
     */
    public function __construct( public CashRegistersService $cashRegistersService )
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle( CashRegisterHistoryAfterDeletedEvent $event ): void
    {
        // ...
    }
}
