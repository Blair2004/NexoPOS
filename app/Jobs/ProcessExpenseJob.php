<?php

namespace App\Jobs;

use App\Models\Expense;
use App\Services\DateService;
use App\Services\ExpenseService;
use App\Traits\NsSerialize;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class ProcessExpenseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, NsSerialize;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct( public Expense $expense )
    {
        $this->prepareSerialization();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle( ExpenseService $expenseService )
    {
        /**
         * @var DateService $date
         */
        $date = app()->make( DateService::class );

        if ( (bool) $this->expense->active && ! $this->expense->recurring && Carbon::parse( $this->expense->scheduled_date )->lessThan( $date->toDateTimeString() ) ) {
            /**
             * if the expense is not recurring and not scheduled
             * we'll immediately trigger it.
             */
            $expenseService->triggerExpense( $this->expense );
        }
    }
}
