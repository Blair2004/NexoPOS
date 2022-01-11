<?php

namespace App\Listeners;

use App\Events\AfterCustomerAccountHistoryCreatedEvent;
use App\Events\ExpenseAfterCreateEvent;
use App\Events\ExpenseAfterRefreshEvent;
use App\Events\ExpenseAfterUpdateEvent;
use App\Events\ExpenseBeforeRefreshEvent;
use App\Events\CashFlowHistoryAfterCreatedEvent;
use App\Events\CashFlowHistoryBeforeDeleteEvent;
use App\Events\CashRegisterHistoryAfterCreatedEvent;
use App\Events\OrderAfterCreatedEvent;
use App\Events\OrderAfterPaymentCreatedEvent;
use App\Events\OrderAfterPaymentStatusChangedEvent;
use App\Events\OrderAfterProductRefundedEvent;
use App\Jobs\AfterExpenseComputedJob;
use App\Jobs\ComputeDashboardExpensesJob;
use App\Jobs\RefreshExpenseJob;
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
    )
    {
        $this->expenseService   =   $expense;
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
                if ( ! $event->expense->recurring && ( bool ) $event->expense->active ) {
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
            fn( $event ) => ComputeDashboardExpensesJob::dispatch( $event )
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
         * this will handled expense history when it's being
         * deleted. This should recalculate the expenses for the specific day.
         */
        $event->listen(
            CashFlowHistoryBeforeDeleteEvent::class,
            fn( $event ) => ComputeDashboardExpensesJob::dispatch( $event )
        );

        /**
         * Will dispatch event for refreshing expenses
         * for a specific date
         */
        $event->listen(
            ExpenseBeforeRefreshEvent::class,
            
            fn( $event ) => RefreshExpenseJob::dispatch( 
                $event->dashboardDay->range_starts, 
                $event->dashboardDay->range_ends 
            )->delay( $event->date )
        );

        /**
         * Once all expenses has been refreshed
         * this job will delete an expense history in
         * case the event was made from a deletion action.
         */
        $event->listen(
            ExpenseAfterRefreshEvent::class,
            fn( $event ) => AfterExpenseComputedJob::dispatch( $event->event )
                ->delay( $event->date )
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
