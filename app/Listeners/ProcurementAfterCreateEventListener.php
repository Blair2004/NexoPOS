<?php

namespace App\Listeners;

use App\Events\ProcurementAfterCreateEvent;
use App\Services\ExpenseService;
use App\Services\ProcurementService;
use App\Services\ProviderService;

class ProcurementAfterCreateEventListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(
        public ProcurementService $procurementService,
        public ProviderService $providerService,
        public ExpenseService $expenseService,
    ) {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle( ProcurementAfterCreateEvent $event )
    {
        $this->procurementService->refresh( $event->procurement );
        $this->providerService->computeSummary( $event->procurement->provider );
        $this->procurementService->handleProcurement( $event->procurement );
        $this->expenseService->handleProcurementExpense( $event->procurement );
    }
}
