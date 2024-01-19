<?php

namespace App\Jobs;

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
    use Dispatchable, InteractsWithQueue, NsSerialize, Queueable;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(public Transaction $transaction)
    {
        $this->prepareSerialization();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(TransactionService $transactionService, DateService $dateService)
    {
        switch ($this->transaction->type) {
            case Transaction::TYPE_SCHEDULED:
                $this->handleScheduledTransaction($transactionService, $dateService);
                break;
            case Transaction::TYPE_DIRECT:
                $this->handleDirectTransaction($transactionService, $dateService);
                break;
        }
    }

    public function handleScheduledTransaction(TransactionService $transactionService, DateService $dateService)
    {
        if (Carbon::parse($this->transaction->scheduled_date)->lessThan($dateService->toDateTimeString())) {
            $transactionService->triggerTransaction($this->transaction);
        }
    }

    public function handleDirectTransaction(TransactionService $transactionService, DateService $dateService)
    {
        $transactionService->triggerTransaction($this->transaction);
    }
}
