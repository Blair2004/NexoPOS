<?php

namespace App\Listeners;

use App\Events\OrderAfterDeletedEvent;
use App\Events\ShouldRefreshReportEvent;
use App\Jobs\UncountDeletedOrderForCashierJob;
use App\Jobs\UncountDeletedOrderForCustomerJob;
use App\Services\CashRegistersService;

class OrderAfterDeletedEventListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(
        public CashRegistersService $cashRegistersService
    ) {
        //
    }

    /**
     * Handle the event.
     *
     * @return void
     */
    public function handle( OrderAfterDeletedEvent $event )
    {
        UncountDeletedOrderForCashierJob::dispatch( $event->order );
        UncountDeletedOrderForCustomerJob::dispatch( $event->order );

        $this->cashRegistersService->deleteRegisterHistoryUsingOrder( $event->order );

        /**
         * We'll instruct NexoPOS to perform
         * a backend jobs to update the report.
         */
        ShouldRefreshReportEvent::dispatch( now()->parse( $event->order->updated_at ) );
    }
}
