<?php

namespace App\Listeners;

use App\Events\ProcurementBeforeDeleteEvent;
use App\Services\ProcurementService;
use App\Services\ProviderService;

class ProcurementBeforeDeleteEventListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(
        public ProcurementService $procurementService,
        public ProviderService $providerService
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
    }
}
