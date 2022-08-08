<?php

namespace App\Listeners;

use App\Events\CashFlowHistoryAfterDeletedEvent;
use App\Jobs\RefreshReportJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class CashFlowHistoryAfterDeletedEventListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle( CashFlowHistoryAfterDeletedEvent $event )
    {
        /**
         * @todo needs to check if
         * the even has the cashFlow instance
         */
        RefreshReportJob::dispatch( $event->cashFlow );
    }
}
