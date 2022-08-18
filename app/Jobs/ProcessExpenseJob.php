<?php

namespace App\Jobs;

use App\Models\Expense;
use App\Services\ExpenseService;
use App\Traits\NsSerialize;
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
        if ( ! $this->expense->recurring && (bool) $this->expense->active ) {
            $expenseService->triggerExpense( $this->expense );
        }
    }
}
