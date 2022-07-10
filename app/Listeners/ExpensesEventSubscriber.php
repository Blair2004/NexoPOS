<?php

namespace App\Listeners;

use App\Events\AfterCustomerAccountHistoryCreatedEvent;
use App\Events\CashFlowHistoryAfterCreatedEvent;
use App\Events\ExpenseAfterCreateEvent;
use App\Events\ExpenseAfterUpdateEvent;
use App\Events\OrderAfterCreatedEvent;
use App\Events\OrderAfterPaymentStatusChangedEvent;
use App\Events\OrderAfterProductRefundedEvent;
use App\Jobs\RefreshReportJob;
use App\Services\ExpenseService;

class ExpensesEventSubscriber
{
    /**
     * @var ExpenseService
     */
    public $expenseService;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(
        ExpenseService $expense
    ) {
        $this->expenseService = $expense;
    }

    public function subscribe( $event )
    {
        /**
         * will record the history of the expense
         * only if it's not recurring
         */
        $event->listen(
            ExpenseAfterCreateEvent::class,
            function( ExpenseAfterCreateEvent $event ) {
                if ( ! $event->expense->recurring && $event->expense->active ) {
                    $this->expenseService->triggerExpense( $event->expense );
                }
            }
        );

        /**
         * will record the history of the expense
         * only if it's not recurring
         */
        $event->listen(
            ExpenseAfterUpdateEvent::class,
            function( ExpenseAfterUpdateEvent $event ) {
                if ( ! $event->expense->recurring && (bool) $event->expense->active ) {
                    $this->expenseService->triggerExpense( $event->expense );
                }
            }
        );

        /**
         * Will dispatch an even to compute
         * the expense record on the dashboard
         */
        $event->listen(
            CashFlowHistoryAfterCreatedEvent::class,
            fn( $event ) => RefreshReportJob::dispatch( $event )
        );

        /**
         * When we detect a payment
         * status has changed from one state to another
         */
        $event->listen(
            OrderAfterPaymentStatusChangedEvent::class,
            fn( OrderAfterPaymentStatusChangedEvent $event ) => $this->expenseService->handlePaymentStatus( $event )
        );

        $event->listen(
            OrderAfterCreatedEvent::class,
            fn( OrderAfterCreatedEvent $event ) => $this->expenseService->handleCreatedOrder( $event->order )
        );

        /**
         * We'll create a record for every customer
         * translaction generated on his account
         */
        $event->listen(
            AfterCustomerAccountHistoryCreatedEvent::class,
            fn( AfterCustomerAccountHistoryCreatedEvent $event ) => $this->expenseService->handleCustomerCredit( $event->customerAccount )
        );

        /**
         * All refunds should create an expense on the system
         */
        $event->listen(
            OrderAfterProductRefundedEvent::class,
            function( OrderAfterProductRefundedEvent $event ) {
                $this->expenseService->createExpenseFromRefund( $event->order, $event->orderProductRefund, $event->orderProduct );
            }
        );
    }
}
