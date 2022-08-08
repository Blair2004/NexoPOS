<?php

namespace App\Listeners;

use App\Events\ProcurementBeforeDeleteProductEvent;
use App\Services\ProcurementService;
use App\Services\ProviderService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

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
    )
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\ProcurementBeforeDeleteProductEvent  $event
     * @return void
     */
    public function handle(ProcurementBeforeDeleteProductEvent $event)
    {
        // ...
    }
}
