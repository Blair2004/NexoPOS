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
    public function handle( OrderCouponAfterCreatedEvent $event)
    {
        $customerCoupon     =   $event->order->customer
            ->coupons()
            ->where( 'coupon_id', $event->orderCoupon->coupon_id )
            ->first();

        /**
         * we'll count usage only if the limit_usage
         * is greather than 0
         */
        if ( $customerCoupon->limit_usage > 0 ) {
            $customerCoupon->usage++;
            
            /**
             * If the usage match the limit. We've reaced the maximum
             * usage for the coupon and we'll then disable it.
             */
            if ( $customerCoupon->usage === $customerCoupon->limit_usage ) {
                $customerCoupon->active     =   false;
            }

            $customerCoupon->save();
        }
    }
}
