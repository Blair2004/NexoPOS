<?php

namespace App\Listeners;

use App\Events\CashRegisterHistoryAfterAllDeletedEvent;
use App\Services\CashRegistersService;

class CashRegisterHistoryAfterAllDeletedEventListener
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
    public function handle( CashRegisterHistoryAfterAllDeletedEvent $event ): void
    {
        $this->cashRegistersService->refreshCashRegister( $event->register );
    }
}
