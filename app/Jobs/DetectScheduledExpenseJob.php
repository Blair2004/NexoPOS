<?php

namespace App\Jobs;

use App\Models\Expense;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class DetectScheduledExpenseJob implements ShouldQueue
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

        $query = Expense::scheduled()
            ->active()
            ->scheduledAfterDate( $startRange->toDateTimeString() )
            ->scheduledBeforeDate( $endRange->toDateTimeString() );

        /**
         * This means we have some valid expenses
         * that needs to be executed at the moment.
         */
        if ( $query->count() > 0 ) {
            $query->get()->each( function( Expense $expense ) {
                ExecuteDelayedExpenseJob::dispatch( $expense );
            });
        }
    }

    public function failed( Throwable $exception )
    {
        Log::error( $exception->getMessage() );
    }
}
