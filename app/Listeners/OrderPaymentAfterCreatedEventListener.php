<?php

namespace App\Listeners;

use App\Events\OrderPaymentAfterCreatedEvent;
use App\Jobs\StoreCustomerPaymentHistoryJob;
use App\Jobs\TrackCashRegisterJob;
use App\Services\AccountingJournalService;

class OrderPaymentAfterCreatedEventListener
{
    public function __construct( private AccountingJournalService $accountingJournalService ) {}

    public function handle( OrderPaymentAfterCreatedEvent $event ): void
    {
        $this->accountingJournalService->postOrderPayment( $event->orderPayment );

        TrackCashRegisterJob::dispatchIf(
            ns()->option->get( 'ns_pos_registers_enabled', 'no' ) === 'yes',
            $event->orderPayment
        );

        StoreCustomerPaymentHistoryJob::dispatch( $event->orderPayment );
    }
}
