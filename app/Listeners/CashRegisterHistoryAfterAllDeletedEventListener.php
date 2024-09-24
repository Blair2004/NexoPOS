<?php

namespace App\Listeners;

use App\Events\CashRegisterHistoryAfterAllDeletedEvent;
use App\Services\CashRegistersService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

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
    public function handle( CashRegisterHistoryAfterAllDeletedEvent $event): void
    {        
        $this->cashRegistersService->refreshCashRegister( $event->register );
    }
}
