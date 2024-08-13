<?php

namespace App\Jobs;

use App\Models\RegisterHistory;
use App\Services\TransactionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RecordCashRegisterHistoryOnTransactionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct( public RegisterHistory $registerHistory )
    {
        //
    }

    /**
     * Execute the job.
     *
     * @deprecated until next minor release
     */
    public function handle( TransactionService $transactionService ): void
    {
        // $transactionService->createTransactionFromRegisterHistory( $this->registerHistory );
    }
}
