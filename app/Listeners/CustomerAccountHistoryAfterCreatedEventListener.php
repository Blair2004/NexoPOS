<?php

namespace App\Listeners;

use App\Events\CustomerAccountHistoryAfterCreatedEvent;
use App\Services\AccountingJournalService;
use App\Services\CustomerService;

class CustomerAccountHistoryAfterCreatedEventListener
{
    public function __construct(
        private CustomerService $customerService,
        private AccountingJournalService $accountingJournalService,
    ) {}

    public function handle( CustomerAccountHistoryAfterCreatedEvent $event ): void
    {
        $this->customerService->updateCustomerAccount( $event->customerAccountHistory );
        $this->accountingJournalService->postWallet( $event->customerAccountHistory );
    }
}
