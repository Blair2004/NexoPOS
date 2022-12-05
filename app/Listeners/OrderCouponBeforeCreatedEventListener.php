<?php

namespace App\Listeners;

use App\Events\OrderCouponBeforeCreatedEvent;
use App\Models\CustomerCoupon;
use App\Services\CustomerService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

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
        $this->customerService->assignCouponUsage( 
            coupon: $event->coupon, 
            customer_id: $event->order->customer_id
        );
    }
}
