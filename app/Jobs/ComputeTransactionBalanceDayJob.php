<?php

namespace App\Jobs;

use App\Models\TransactionHistory;
use App\Services\TransactionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ComputeTransactionBalanceDayJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct( public TransactionHistory $transactionHistory )
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle( TransactionService $transactionService ): void
    {
        $transactionService->computeTransactionDayBalance( $this->transactionHistory );
    }
}
