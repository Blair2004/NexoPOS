<?php

namespace App\Listeners;

use App\Events\OrderCouponBeforeCreatedEvent;
use App\Services\CustomerService;

class OrderCouponBeforeCreatedEventListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct( private CustomerService $customerService )
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle( OrderCouponBeforeCreatedEvent $event )
    {
        // ...
    }
}
