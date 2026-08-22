<?php

namespace App\Listeners;

use App\Events\OrderAfterProductRefundedEvent;
use App\Jobs\CreateExpenseFromRefundJob;
use App\Services\AccountingJournalService;
use Illuminate\Support\Facades\Bus;

class OrderAfterProductRefundedEventListener
{
    public function __construct( private AccountingJournalService $accountingJournalService ) {}

    public function handle( OrderAfterProductRefundedEvent $event ): void
    {
        $this->accountingJournalService->postReturnedProduct( $event->orderProductRefund );

        Bus::chain( [
            new CreateExpenseFromRefundJob(
                order: $event->order,
                orderProduct: $event->orderProduct,
                orderProductRefund: $event->orderProductRefund
            ),
        ] )->dispatch();
    }
}
