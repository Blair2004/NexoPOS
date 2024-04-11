<?php

namespace App\Listeners;

use App\Events\OrderAfterCreatedEvent;
use App\Jobs\ComputeDayReportJob;
use App\Jobs\IncreaseCashierStatsJob;
use App\Jobs\ProcessAccountingRecordFromSaleJob;
use App\Jobs\ProcessCashRegisterHistoryJob;
use App\Jobs\ProcessCustomerOwedAndRewardsJob;
use App\Jobs\ResolveInstalmentJob;
use App\Jobs\TrackOrderCouponsJob;
use Illuminate\Support\Facades\Bus;

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
     * @param  object $event
     * @return void
     */
    public function handle( OrderAfterCreatedEvent $event )
    {
        Bus::chain( [
            new ProcessCashRegisterHistoryJob( $event->order ),
            new IncreaseCashierStatsJob( $event->order ),
            new ProcessCustomerOwedAndRewardsJob( $event->order ),
            new TrackOrderCouponsJob( $event->order ),
            new ResolveInstalmentJob( $event->order ),
            new ProcessAccountingRecordFromSaleJob( $event->order ),
            new ComputeDayReportJob,
        ] )->dispatch();
    }
}
