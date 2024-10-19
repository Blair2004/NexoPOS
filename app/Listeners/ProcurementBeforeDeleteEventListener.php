<?php

namespace App\Listeners;

use App\Events\ProcurementBeforeDeleteEvent;
use App\Services\ProcurementService;
use App\Services\ProviderService;
use App\Services\TransactionService;

class ProcurementBeforeDeleteEventListener
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
    public function handle( ProcurementBeforeDeleteEvent $event )
    {
        $this->procurementService->attemptProductsStockRemoval( $event->procurement );
        $this->procurementService->deleteProcurementProducts( $event->procurement );
        $this->transactionService->deleteProcurementTransactions( $event->procurement );
    }
}
