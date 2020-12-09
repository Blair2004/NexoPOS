<?php

namespace App\Listeners;

use App\Events\ExpenseAfterCreateEvent;
use App\Events\ExpenseHistoryAfterCreatedEvent;
use App\Events\ExpenseHistoryBeforeDeleteEvent;
use App\Jobs\ComputeDashboardExpensesJob;
use App\Services\ExpenseService;

class ExpensesEventSubscriber
{
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
            function( $event ) {
                if ( ! $event->expense->recurring ) {
                    $this->expenseService->triggerExpense( $event->expense );
                }
            }
        );

        /**
         * Will dispatch an even to compute
         * the expense record on the dashboard
         */
        $event->listen(
            ExpenseHistoryAfterCreatedEvent::class,
            fn( $event ) => ComputeDashboardExpensesJob::dispatch( $event )
        );

        /**
         * this will handled expense history when it's being
         * deleted. This should recalculate the expenses for the specific day.
         */
        $event->listen(
            ExpenseHistoryBeforeDeleteEvent::class,
            fn( $event ) => ComputeDashboardExpensesJob::dispatch( $event )
        );
    }
}
