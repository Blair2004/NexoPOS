<?php

namespace App\Listeners;

use App\Events\OrderAfterPaymentStatusChangedEvent;
use App\Events\OrderAfterUpdatedEvent;
use App\Jobs\ComputeDayReportJob;
use App\Jobs\IncreaseCashierStatsJob;
use App\Jobs\ProcessAccountingRecordFromSale;
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
            new ProcessAccountingRecordFromSale( $event->newOrder ),
            new ComputeDayReportJob,
        ] )->dispatch();

        /**
         * if the order payment status has changed from the 
         * previous order, we need to dispatch an event OrderAfterPaymentStatusChangedEvent
         */
        if ( $event->newOrder->payment_status != $event->prevOrder->payment_status ) {
            event( new OrderAfterPaymentStatusChangedEvent( 
                order: $event->newOrder, 
                previous: $event->prevOrder->payment_status,
                new: $event->newOrder->payment_status 
            ) );
        }
    }
}
