<?php

namespace App\Listeners;

use App\Events\TransactionsHistoryAfterCreatedEvent;
use App\Jobs\AccountingReflectionJob;
use App\Services\ReportService;

class TransactionsHistoryAfterCreatedEventListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct( public ReportService $reportService )
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle( TransactionsHistoryAfterCreatedEvent $event )
    {
        if ( ! $event->transactionHistory->is_reflection ) {
            AccountingReflectionJob::dispatch( $event->transactionHistory );
        }

        $this->reportService->computeDayReport();
    }
}
