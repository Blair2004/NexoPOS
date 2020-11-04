<?php

namespace App\Listeners;

use App\Events\AfterCustomerAccountHistoryCreatedEvent;
use App\Services\CustomerService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class CustomerEventSubscriber
{
    /**
     * @var CustomerService
     */
    protected $customerService;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(
        CustomerService $customerService
    )
    {
        $this->customerService      =   $customerService;
    }

    public function subscribe( $event )
    {
        $event->listen(
            AfterCustomerAccountHistoryCreatedEvent::class,
            fn( $event ) =>  $this->customerService->updateCustomerAccount( $event->customerAccount )
        );
    }
}
