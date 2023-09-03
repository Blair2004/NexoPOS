<?php

namespace App\Listeners;

use App\Events\CustomerBeforeDeletedEvent;
use App\Services\CustomerService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class CustomerBeforeDeletedEventListener
{
    /**
     * Create the event listener.
     */
    public function __construct(
        public CustomerService $customerService
    )
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(CustomerBeforeDeletedEvent $event): void
    {
        $this->customerService->deleteCustomerAttributes( $event->customer->id );
    }
}
