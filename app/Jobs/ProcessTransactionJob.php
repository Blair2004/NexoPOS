<?php

namespace App\Jobs;

use App\Models\Expense;
use App\Models\Transaction;
use App\Services\DateService;
use App\Services\TransactionService;
use App\Traits\NsSerialize;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class ProcessTransactionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, NsSerialize;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct( public Transaction $transaction )
    {
        $this->prepareSerialization();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle( TransactionService $transactionService )
    {
        /**
         * @var DateService $date
         */
        $date = app()->make( DateService::class );

        if ( (bool) $this->transaction->active && ! $this->transaction->recurring && Carbon::parse( $this->transaction->scheduled_date )->lessThan( $date->toDateTimeString() ) ) {
            /**
             * if the expense is not recurring and not scheduled
             * we'll immediately trigger it.
             */
            $transactionService->triggerTransaction( $this->transaction );
        }
    }
}
