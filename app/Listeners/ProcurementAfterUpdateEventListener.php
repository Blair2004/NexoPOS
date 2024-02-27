<?php

namespace App\Listeners;

use App\Events\ProcurementAfterUpdateEvent;
use App\Services\ProcurementService;
use App\Services\ProviderService;
use App\Services\TransactionService;

class ProcurementAfterUpdateEventListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(
        public ProcurementService $procurementService,
        public ProviderService $providerService,
        public TransactionService $transactionService
    ) {
        //
    }

    /**
     * Handle the event.
     *
     * @return void
     */
    public function handle( ProcurementAfterUpdateEvent $event )
    {
        $this->procurementService->refresh( $event->procurement );
        $this->providerService->computeSummary( $event->procurement->provider );
        $this->procurementService->handleProcurement( $event->procurement );
        $this->transactionService->handleProcurementTransaction( $event->procurement );
    }
}
