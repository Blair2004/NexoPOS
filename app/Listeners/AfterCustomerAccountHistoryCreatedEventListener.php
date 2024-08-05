<?php

namespace App\Listeners;

use App\Events\AfterCustomerAccountHistoryCreatedEvent;
use App\Services\CustomerService;
use App\Services\TransactionService;

class AfterCustomerAccountHistoryCreatedEventListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(
        public CustomerService $customerService,
        public TransactionService $transactionService
    ) {
        //
    }

    /**
     * Handle the event.
     *
     * @return void
     */
    public function handle( AfterCustomerAccountHistoryCreatedEvent $event )
    {
        /**
         * @todo implement customer account history in accounting part
         */
        $this->customerService->updateCustomerAccount( $event->customerAccount );
    }
}
