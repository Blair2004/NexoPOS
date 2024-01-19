<?php

namespace App\Jobs;

use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class DetectScheduledTransactionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $startRange = ns()->date->copy();
        $endRange = ns()->date->copy();

        $startRange->setSeconds(0);
        $endRange->setSeconds(59);

        $query = Transaction::scheduled()
            ->with('account')
            ->active()
            ->scheduledAfterDate($startRange->toDateTimeString())
            ->scheduledBeforeDate($endRange->toDateTimeString());

        /**
         * This means we have some valid transactions
         * that needs to be executed at the moment.
         */
        if ($query->count() > 0) {
            $query->get()->each(function (Transaction $transaction) {
                ExecuteDelayedTransactionJob::dispatch($transaction);
            });
        }
    }

    public function failed(Throwable $exception)
    {
        Log::error($exception->getMessage());
    }
}
