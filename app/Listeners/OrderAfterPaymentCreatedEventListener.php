<?php

namespace App\Listeners;

use App\Events\OrderAfterPaymentCreatedEvent;
use App\Jobs\TrackCashRegisterJob;

class OrderAfterPaymentCreatedEventListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle( OrderAfterPaymentCreatedEvent $event ): void
    {
        TrackCashRegisterJob::dispatchIf(
            ns()->option->get( 'ns_pos_registers_enabled', 'no' ) === 'yes',
            $event->orderPayment
        );
    }
}
