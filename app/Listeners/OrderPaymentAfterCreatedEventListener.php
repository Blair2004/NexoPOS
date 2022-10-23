<?php

namespace App\Listeners;

use App\Events\OrderAfterPaymentCreatedEvent;

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
        // ...
    }
}
