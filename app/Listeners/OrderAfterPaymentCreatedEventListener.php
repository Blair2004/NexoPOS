<?php

namespace App\Listeners;

use App\Events\OrderAfterPaymentCreatedEvent;
use App\Jobs\TrackCashRegisterJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class OrderAfterPaymentCreatedEventListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle( OrderAfterPaymentCreatedEvent $event): void
    {
        TrackCashRegisterJob::dispatch( $event->orderPayment );
    }
}
