<?php

namespace App\Listeners;

use App\Events\ProcurementAfterHandledEvent;
use App\Models\Procurement;
use App\Services\AccountingJournalService;

class ProcurementAfterHandledEventListener
{
    public function __construct( private AccountingJournalService $accountingJournalService ) {}

    public function handle( ProcurementAfterHandledEvent $event ): void
    {
        if ( $event->procurement->delivery_status !== Procurement::STOCKED ) {
            return;
        }

        $this->accountingJournalService->postProcurementReceipt( $event->procurement );

        if ( $event->procurement->payment_status === Procurement::PAYMENT_PAID ) {
            $this->accountingJournalService->postProcurementPayment( $event->procurement );
        }
    }
}
