<?php

namespace App\Listeners;

use App\Events\ExpenseAfterCreateEvent;
use App\Events\ExpenseAfterRefreshEvent;
use App\Events\ExpenseBeforeRefreshEvent;
use App\Events\ExpenseHistoryAfterCreatedEvent;
use App\Events\ExpenseHistoryBeforeDeleteEvent;
use App\Jobs\AfterExpenseComputedJob;
use App\Jobs\ComputeDashboardExpensesJob;
use App\Jobs\RefreshExpenseJob;
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
                if ( ! $event->expense->recurring && $event->expense->active ) {
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

        /**
         * Will dispatch event for refreshing expenses
         * for a specific date
         */
        $event->listen(
            ExpenseBeforeRefreshEvent::class,
            fn( $event ) => RefreshExpenseJob::dispatch( $event->dashboardDay )->delay( $event->date )
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
    }
}
