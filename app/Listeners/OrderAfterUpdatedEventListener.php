<?php

namespace App\Listeners;

use App\Events\OrderAfterUpdatedEvent;
use App\Jobs\ComputeDayReportJob;
use App\Jobs\IncreaseCashierStatsJob;
use App\Jobs\ProcessAccountingRecordFromSaleJob;
use App\Jobs\ProcessCashRegisterHistoryJob;
use App\Jobs\ProcessCustomerOwedAndRewardsJob;
use App\Jobs\ResolveInstalmentJob;
use App\Jobs\TrackOrderCouponsJob;
use Illuminate\Support\Facades\Bus;

class OrderAfterUpdatedEventListener
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
     * @return void
     */
    public function handle( OrderAfterUpdatedEvent $event )
    {
        Bus::chain( [
            new ProcessCashRegisterHistoryJob( $event->newOrder ),
            new IncreaseCashierStatsJob( $event->newOrder ),
            new ProcessCustomerOwedAndRewardsJob( $event->newOrder ),
            new TrackOrderCouponsJob( $event->newOrder ),
            new ResolveInstalmentJob( $event->newOrder ),
            new ProcessAccountingRecordFromSaleJob( $event->newOrder ),
            new ComputeDayReportJob,
        ] )->dispatch();
    }
}
