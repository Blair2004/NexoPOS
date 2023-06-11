<?php

namespace App\Listeners;

use App\Events\AfterCustomerAccountHistoryCreatedEvent;
use App\Services\CustomerService;
use App\Services\ExpenseService;

class AfterCustomerAccountHistoryCreatedEventListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(
        public CustomerService $customerService,
        public ExpenseService $expenseService
    ) {
        //
    }

    /**
     * Handle the event.
     *
     * @return void
     */
    public function handle(AfterCustomerAccountHistoryCreatedEvent $event)
    {
        $this->expenseService->handleCustomerCredit( $event->customerAccount );
        $this->customerService->updateCustomerAccount( $event->customerAccount );
    }
}
