<?php

namespace App\Listeners;

use App\Events\OrderAfterUpdatedEvent;
use App\Jobs\ComputeDayReportJob;
use App\Jobs\IncreaseCashierStatsJob;
use App\Jobs\ProcessCustomerOwedAndRewardsJob;
use App\Jobs\RecordOrderChangeJob;
use App\Jobs\ResolveInstalmentJob;
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
            new IncreaseCashierStatsJob( $event->order ),
            new ProcessCustomerOwedAndRewardsJob( $event->order ),
            new ResolveInstalmentJob( $event->order ),
            new ComputeDayReportJob,
            new RecordOrderChangeJob( $event->order ),
        ] )->dispatch();
    }
}
