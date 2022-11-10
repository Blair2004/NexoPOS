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
        $date   =   app()->make( DateService::class );

        if ( (bool) $this->expense->active && ! $this->expense->recurring ) {
            /**
             * if the expense is not recurring and not scheduled
             * we'll immediately trigger it.
             */
            if ( Carbon::parse( $this->expense->scheduled_date )->lessThan( $date )  ) {
                $expenseService->triggerExpense( $this->expense );
            } else {
                /**
                 * Here we'll schedule the expense to run
                 * at the scheduled date. If for any reason while running the scheduled date
                 * no longer match the server date, the execution will be prevented.
                 */
                $diffInMinutes  =   ns()
                    ->date
                    ->diffInMinutes( ns()->date->createFromTimeString( 
                        $this->expense->scheduled_date,
                        ns()->date->getTimezone()
                    ) );

                ExecuteDelayedExpenseJob::dispatch( $this->expense )
                    ->delay( now()->copy()->addMinutes( $diffInMinutes ) ); // 
            }
        }
    }
}
