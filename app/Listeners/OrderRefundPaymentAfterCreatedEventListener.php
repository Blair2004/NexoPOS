<?php

namespace App\Listeners;

use App\Events\OrderRefundPaymentAfterCreatedEvent;

class OrderRefundPaymentAfterCreatedEventListener
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
     * @param  object $event
     * @return void
     */
    public function handle( OrderRefundPaymentAfterCreatedEvent $event )
    {
        /**
         * the refund can't always be made from the register where the order
         * has been created. We need to consider the fact that refund can't lead
         * to cash drawer disbursement.
         */
    }
}
