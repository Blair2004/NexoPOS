<?php

namespace App\Listeners;

use App\Events\OrderBeforePaymentCreatedEvent;
use App\Jobs\CheckCustomerAccountJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class OrderBeforePaymentCreatedEventListener
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
     * @return void
     */
    public function handle( OrderBeforePaymentCreatedEvent $event)
    {
        CheckCustomerAccountJob::dispatchSync( $event->customer, $event->payment );
    }
}
