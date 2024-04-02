<?php

namespace App\Listeners;

use App\Events\ProcurementAfterDeleteEvent;
use App\Models\Provider;
use App\Services\ProcurementService;
use App\Services\ProviderService;

class ProcurementAfterDeleteEventListener
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
    public function handle( ProcurementAfterDeleteEvent $event )
    {
        $this->providerService->computeSummary(
            Provider::find( $event->procurement_data[ 'provider_id' ] )
        );
    }
}
