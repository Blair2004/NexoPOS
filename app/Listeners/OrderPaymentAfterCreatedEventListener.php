<?php

namespace App\Listeners;

use App\Events\OrderPaymentAfterCreatedEvent;
use App\Jobs\StoreCustomerPaymentHistoryJob;
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
    public function handle( OrderPaymentAfterCreatedEvent $event )
    {
        TrackCashRegisterJob::dispatchIf(
            ns()->option->get( 'ns_pos_registers_enabled', 'no' ) === 'yes',
            $event->orderPayment
        );

        StoreCustomerPaymentHistoryJob::dispatch( $event->orderPayment );
    }
}
