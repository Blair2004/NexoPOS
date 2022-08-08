<?php

namespace App\Listeners;

use App\Events\ProcurementBeforeUpdateEvent;
use App\Services\ProcurementService;
use App\Services\ProviderService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class ProcurementBeforeUpdateEventListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(
        public ProcurementService $procurementService,
        public ProviderService $providerService
    )
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\ProcurementBeforeUpdateEvent  $event
     * @return void
     */
    public function handle(ProcurementBeforeUpdateEvent $event)
    {
        $this->providerService->cancelPaymentForProcurement( $event->procurement );
    }
}
