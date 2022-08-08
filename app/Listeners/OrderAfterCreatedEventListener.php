<?php

namespace App\Listeners;

use App\Events\OrderAfterCreatedEvent;
use App\Jobs\ComputeDayReportJob;
use App\Jobs\IncreaseCashierStatsJob;
use App\Jobs\ProcessCashRegisterHistoryJob;
use App\Jobs\ProcessCustomerOwedAndRewardsJob;
use App\Jobs\ResolveInstalmentJob;
use App\Jobs\TrackOrderCouponsJob;

class OrderAfterCreatedEventListener
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
     *
     * @param  object  $event
     * @return void
     */
    public function handle( OrderAfterCreatedEvent $event )
    {
        ProcessCashRegisterHistoryJob::dispatch( $event->order );
        IncreaseCashierStatsJob::dispatch( $event->order );
        ProcessCustomerOwedAndRewardsJob::dispatch( $event->order );
        TrackOrderCouponsJob::dispatch( $event->order );
        ResolveInstalmentJob::dispatch( $event->order );
        ComputeDayReportJob::dispatch();
    }
}
