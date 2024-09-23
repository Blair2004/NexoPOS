<?php

namespace App\Listeners;

use App\Events\CustomerRewardAfterCreatedEvent;
use App\Jobs\ApplyCustomerRewardJob;

class CustomerRewardAfterCreatedEventListener
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
    public function handle( CustomerRewardAfterCreatedEvent $event )
    {
        ApplyCustomerRewardJob::dispatch( $event->customerReward->customer, $event->customerReward, $event->customerReward->reward );
    }
}
