<?php

namespace App\Listeners;

use App\Events\OrderVoidedEvent;
use App\Jobs\UncountDeletedOrderForCashierJob;
use App\Jobs\UncountDeletedOrderForCustomerJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class OrderVoidedEventListener
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
    public function handle( OrderVoidedEvent $event )
    {
        UncountDeletedOrderForCashierJob::dispatch( $event->order );
        UncountDeletedOrderForCustomerJob::dispatch( $event->order );
    }
}
