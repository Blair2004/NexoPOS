<?php

namespace App\Listeners;

use App\Events\OrderCouponAfterCreatedEvent;
use App\Jobs\TrackOrderCouponsJob;

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
        TrackOrderCouponsJob::dispatch( $event->orderCoupon->order );
    }
}
