<?php

namespace App\Listeners;

use App\Events\ProcurementBeforeDeleteProductEvent;
use App\Services\ProcurementService;
use App\Services\ProviderService;

class ProcurementBeforeDeleteProductEventListener
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
    public function handle( ProcurementBeforeDeleteProductEvent $event )
    {
        // ...
    }
}
