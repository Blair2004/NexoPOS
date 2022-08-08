<?php

namespace App\Listeners;

use App\Events\OrderAfterUpdatedEvent;
use App\Jobs\ProcessCustomerOwedAndRewardsJob;
use App\Jobs\ResolveInstalmentJob;
use Illuminate\Support\Facades\Bus;

class OrderAfterUpdatedEventListener
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
     * @return void
     */
    public function handle( OrderAfterUpdatedEvent $event)
    {
        Bus::chain([
            new ProcessCustomerOwedAndRewardsJob( $event->order ),
            new ResolveInstalmentJob( $event->order ),
        ])->dispatch();
    }
}
