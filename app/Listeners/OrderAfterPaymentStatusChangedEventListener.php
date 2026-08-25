<?php

namespace App\Listeners;

use App\Events\OrderAfterPaymentStatusChangedEvent;
use App\Models\Order;
use App\Services\AccountingJournalService;
use App\Services\CashRegistersService;
use App\Services\OrdersService;

class OrderAfterPaymentStatusChangedEventListener
{
    public function __construct(
        public OrdersService $ordersService,
        public CashRegistersService $cashRegistersService,
        private AccountingJournalService $accountingJournalService,
    ) {}

    public function handle( OrderAfterPaymentStatusChangedEvent $event ): void
    {
        if (
            in_array( $event->previous, [ null, Order::PAYMENT_HOLD, Order::PAYMENT_UNPAID ], true )
            && in_array( $event->new, [ Order::PAYMENT_PAID, Order::PAYMENT_PARTIALLY, Order::PAYMENT_UNPAID ], true )
        ) {
            $this->ordersService->saveOrderProductHistory( $event->order );
        }

        if ( $event->previous !== Order::PAYMENT_VOID && $event->new === Order::PAYMENT_VOID ) {
            $this->accountingJournalService->postOrderVoid( $event->order );
            $this->ordersService->returnVoidProducts( $event->order );
        }
    }
}
