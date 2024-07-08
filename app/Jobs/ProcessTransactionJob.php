<?php

namespace App\Jobs;

use App\Models\Transaction;
use App\Services\DateService;
use App\Services\TransactionService;
use App\Traits\NsSerialize;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class ProcessTransactionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, NsSerialize, Queueable;

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
    public function handle( TransactionService $transactionService, DateService $dateService )
    {
        if ( in_array( $this->transaction->type, [
            Transaction::TYPE_SCHEDULED,
            Transaction::TYPE_ENTITY,
        ] ) ) {
            $this->handlePrepareScheduledAndEntityTransaction( $transactionService, $dateService );
        } elseif ( $this->transaction->type === Transaction::TYPE_DIRECT ) {
            $this->handleDirectTransaction( $transactionService, $dateService );
        }
    }

    public function handlePrepareScheduledAndEntityTransaction( TransactionService $transactionService, DateService $dateService )
    {
        $transactionService->prepareTransactionHistoryRecord( $this->transaction );
    }

    public function handleDirectTransaction( TransactionService $transactionService, DateService $dateService )
    {
        $transactionService->triggerTransaction( $this->transaction );
    }
}
