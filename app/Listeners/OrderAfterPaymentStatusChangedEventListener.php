<?php

namespace App\Listeners;

use App\Events\OrderAfterPaymentStatusChangedEvent;
use App\Jobs\ProcessCustomerOwedAndRewardsJob;
use App\Jobs\RecordRegisterHistoryUsingPaymentStatusJob;

class OrderAfterPaymentStatusChangedEventListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @return void
     */
    public function handle( OrderAfterPaymentStatusChangedEvent $event )
    {
        ProcessCustomerOwedAndRewardsJob::dispatch( $event->order );
        RecordRegisterHistoryUsingPaymentStatusJob::dispatch( $event->order, $event->previous, $event->new );
    }
}
