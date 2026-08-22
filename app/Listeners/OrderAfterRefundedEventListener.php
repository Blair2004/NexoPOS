<?php

namespace App\Listeners;

use App\Events\OrderAfterRefundedEvent;
use App\Jobs\DecreaseCustomerPurchasesJob;
use App\Jobs\ReduceCashierStatsFromRefundJob;
use App\Jobs\RefreshOrderJob;
use App\Services\AccountingJournalService;
use Illuminate\Support\Facades\Bus;

class OrderAfterRefundedEventListener
{
    public function __construct( private AccountingJournalService $accountingJournalService ) {}

    public function handle( OrderAfterRefundedEvent $event ): void
    {
        $this->accountingJournalService->postRefund( $event->orderRefund );

        Bus::chain( [
            new RefreshOrderJob( $event->order ),
            new ReduceCashierStatsFromRefundJob( $event->order, $event->orderRefund ),
            new DecreaseCustomerPurchasesJob( $event->order->customer, $event->orderRefund->total ),
        ] )->dispatch();
    }
}
