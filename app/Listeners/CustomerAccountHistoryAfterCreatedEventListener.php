<?php

namespace App\Listeners;

use App\Events\CustomerAccountHistoryAfterCreatedEvent;
use App\Services\CustomerService;
use App\Services\TransactionService;

class CustomerAccountHistoryAfterCreatedEventListener
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
    public function handle( CustomerAccountHistoryAfterCreatedEvent $event )
    {
        /**
         * @todo implement customer account history in accounting part
         */
        $this->customerService->updateCustomerAccount( $event->customerAccountHistory );
    }
}
