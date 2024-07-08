<?php

namespace App\Jobs;

use App\Models\TransactionHistory;
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
        $query = TransactionHistory::scheduled()
            ->triggerDate( ns()->date->toDateTimeString() );

        /**
         * This means we have some valid transactions
         * that needs to be executed at the moment.
         */
        if ( $query->count() > 0 ) {
            $query->get()->each( function ( TransactionHistory $transaction ) {
                ExecuteDelayedTransactionJob::dispatch( $transaction );
            } );
        }
    }

    public function failed( Throwable $exception )
    {
        Log::error( $exception->getMessage() );
    }
}
