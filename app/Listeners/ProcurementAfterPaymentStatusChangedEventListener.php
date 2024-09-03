<?php

namespace App\Listeners;

use App\Events\ProcurementAfterPaymentStatusChangedEvent;
use App\Services\ProcurementService;
use App\Services\TransactionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class ProcurementAfterPaymentStatusChangedEventListener
{
    /**
     * Create the event listener.
     */
    public function __construct( public ProcurementService $procurementService, public TransactionService $transactionService )
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle( ProcurementAfterPaymentStatusChangedEvent $event ): void
    {
        $this->transactionService->handleProcurementPaymentStatusChanged( 
            procurement: $event->procurement, 
            previous: $event->previous, 
            new: $event->new 
        );
    }
}
