<?php

namespace App\Listeners;

use App\Events\ProcurementAfterDeleteProductEvent;
use App\Services\ProcurementService;
use App\Services\ProviderService;

class ProcurementAfterDeleteProductEventListener
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
    public function handle( ProcurementAfterDeleteProductEvent $event )
    {
        $this->procurementService->refresh( $event->procurement );
    }
}
