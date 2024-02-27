<?php

namespace App\Listeners;

use App\Events\OrderCouponAfterCreatedEvent;

class OrderCouponAfterCreatedEventListener
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
    public function handle( OrderCouponAfterCreatedEvent $event )
    {
        // ...
    }
}
