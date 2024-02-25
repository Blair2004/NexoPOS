<?php

namespace App\Listeners;

use App\Events\ProcurementAfterUpdateProductEvent;
use App\Services\ProcurementService;
use App\Services\ProviderService;

class ProcurementAfterUpdateProductEventListener
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
    public function handle( ProcurementAfterUpdateProductEvent $event )
    {
        $this->procurementService->procurementStockEntry( $event->product, $event->fields );
        $this->procurementService->refresh( $event->product->procurement );
    }
}
