<?php

namespace App\Listeners;

use App\Events\OrderAfterPaymentCreatedEvent;
use App\Jobs\TrackCashRegisterJob;

class OrderPaymentAfterCreatedEventListener
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
     */
    public function handle( OrderAfterPaymentCreatedEvent $event )
    {
        TrackCashRegisterJob::dispatchIf(
            ns()->option->get( 'ns_pos_registers_enabled', 'no' ) === 'yes',
            $event->orderPayment
        );
    }
}
